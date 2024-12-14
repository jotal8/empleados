<?php

namespace App\Security;

use throwable;
use App\Entity\Usuario;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use App\Repository\UsuarioRepository;
use App\Services\JwtService;


class CustomAuthenticator extends AbstractGuardAuthenticator
{
    private $usuarioRepository;
    private EntityManagerInterface $EntityManagerInterface;

    /**
     * Constructor para Inyectar EntityManagerInterface y UsuarioRepository
     * 
     * @param EntityManagerInterface $entityManagerInterface
     * @param UsuarioRepository $UsuarioRepository
     */
    public function __construct(EntityManagerInterface $entityManagerInterface, UsuarioRepository $usuarioRepository)
    {
        $this->EntityManagerInterface = $entityManagerInterface;
        $this->usuarioRepository = $usuarioRepository;
    }

    /**
     * Este metodo verifica si hay un header con el nombre authorization
     * @param Request $request
     * @return bool
     */
    public function supports(Request $request)
    {
        return $request->headers->has('Authorization');
    }

    /**
     * Si es verdadero el valor retornado por supports 
     * getCredentials es usado internamente por symfony para extraer el token
     *
     * @param Request $request
     * @return array
     */
    public function getCredentials(Request $request): array
    {
       
        $token = $request->headers->get('Authorization');

        if (!$token || strpos($token, 'Bearer ') !== 0) {
            throw new CustomUserMessageAuthenticationException('Token no encontrado o mal formado');
        }
        
        $token = str_replace('Bearer ','', $token);

        return [
            'token' => trim($token)
        ];
    }

    /**
     * Método requerido para iniciar la autenticación ya que no se encuentra autenticado
     * 
     * @param Request $request
     * @param AuthenticationException $authException
     * 
     * @return JsonResponse
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $data = [
            'success' => 0,
            'message' => 'Autenticacion requerida'
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);

    }

    /**
     * Con este metodo asociaremos correctamente el token con el usuario del sistema
     * 
     * @param array $credentials
     * @param UserProviderInterface $userProvider
     * 
     * @return UserInterface
     */
    public function getUser($credentials, UserProviderInterface $userProvider): ?UserInterface
    {
        $token = $credentials['token'];
        try {
            $userId = JwtService::getUserId($token);
            $usuario = $this->usuarioRepository->findById($userId);
  
            return $usuario;
        } catch (throwable $th) {
            throw new CustomUserMessageAuthenticationException(
                $th->getMessage() ?? 'Token no encontrado o mal formado'
            );
        }
    }

    /**
     * Se require si se usa otro metodo de autenticacion. en este caso solo para token se retorna true
     * 
     * @param array $credentials
     * @param UserInterface $user
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }

    /**
     * Si no fue posible autenticar se retorna el mensaje del error.
     * 
     * @param array $credentials
     * @param UserInterface $user
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData()),
            'success'  => 0
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * No es necesario retornar algo ya que la solicitud si es exitosa pasa derecho al endpoint destino
     * 
     * @param Request $request
     * @param TokenInterface $token
     * @param string $providerKey
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey)
    {
        return null;
    }

    /**
     * Este metodo se utiliza para recordar la sesion activa del funcionario, por ahora 
     * lo dejamos desahiblitado para esta aplicacion
     * 
     * @return bool
     */
    public function supportsRememberMe(): bool
    {
        return false;
    }
}
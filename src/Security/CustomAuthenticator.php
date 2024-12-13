<?php

namespace App\Security;

use App\Entity\Usuario;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use App\Repository\UsuarioRepository;

class CustomAuthenticator extends AbstractGuardAuthenticator
{
    private $usuarioRepository;
    private EntityManagerInterface $EntityManagerInterface;

    // Inyectar el UsuarioRepository y JWTTokenManager
    public function __construct(EntityManagerInterface $entityManagerInterface, UsuarioRepository $usuarioRepository)
    {
        $this->EntityManagerInterface = $entityManagerInterface;
        $this->usuarioRepository = $usuarioRepository;
    }

    /**
     * Called on every request. Return whatever credentials you want to
     * be passed to getUser() as $credentials.
     *
     * @param Request $request
     * @return array
     */
    public function getCredentials(Request $request): array
    {
        $token = $request->headers->get('Authorization');

        // Verificar si el token está presente
        if (!$token || strpos($token, 'Bearer ') !== 0) {
            throw new AuthenticationException('Token no encontrado o mal formado');
        }

        // Extraer el token
        return [
            'token' => substr($token, 7) // quitar "Bearer " del comienzo
        ];
    }

    /**
     * Método requerido para iniciar la autenticación
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        // Generalmente rediriges al usuario o retornas un mensaje de error
    }

    public function getUser($credentials, UserProviderInterface $userProvider): ?UserInterface
    {
        $token = $credentials['token'];

        try {
            // Decodificar y validar el token JWT
            $data = $this->jwtManager->decodeFromJsonWebToken($token);

            dd($data, 'hola');
            $userId = $data['id'] ?? null; // Suponiendo que el JWT contiene el user_id

            if (!$userId) {
                throw new AuthenticationException('Token inválido: no contiene un user_id');
            }

            // Buscar el usuario en la base de datos
            $usuario = $this->usuarioRepository->find($userId);

            if (!$usuario) {
                throw new AuthenticationException('Usuario no encontrado');
            }

            return $usuario;

        } catch (\Exception $e) {
            throw new AuthenticationException('Token inválido: ' . $e->getMessage());
        }
    }

    /**
     * Verificar las credenciales del usuario (en este caso no hacemos nada más, ya que el token es suficiente)
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        return true; // No necesitamos verificar las credenciales si el token es válido
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData()),
            'logout'  => 1
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey)
    {
        return null;
    }

    public function supports(Request $request)
    {
        // Verifica si esta autenticación debe ser utilizada (cuando haya un Authorization header)
        return $request->headers->has('Authorization');
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }

}
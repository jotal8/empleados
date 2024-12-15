<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Services\JwtService;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\Usuario;
use Exception;
use throwable;


/**
 * @Route("/api/public", name="api_public", methods={"POST"})
 */
class PublicController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface  $passwordHasher;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface  $passwordHasher
        )
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    /**
     * @Route("/login", name="login")
     */
    public function login(
        Request $Request
    ): JsonResponse
    {
        $response = [
           'message' => '',
           'success' => false,
           'token' => ''
        ];
        try{
            $email = $Request->get('email');
            $password = $Request->get('password');

            if(!$email || !$password){
                throw new Exception('Faltan parametros necesarios para la autenticación!');
            }

            $UsuarioRepository = $this->entityManager->getRepository(Usuario::class);
            $Usuario = $UsuarioRepository->findByEmail($email);

            if(!$Usuario){
                throw new Exception('El usuario no se encuentra registrado!');
            }

            if(!$this->passwordHasher->isPasswordValid($Usuario, $password)){
                throw new Exception('El password es incorrecto!');
            }

            $token = JwtService::create([
                'id'   => $Usuario->getId(),
                'rol' => $Usuario->getRol()
            ], 3600);

            $response = [
                'message' => 'Login exitoso!!',
                'success' => true,
                'token'   => $token,
                'data'    => [
                    'nombre' => "{$Usuario->getNombres()} {$Usuario->getApellidos()}",
                    'rol'     => $Usuario->getRol()
                ]
            ];
        }catch (throwable $th){
            $response['message'] = $th->getMessage();
        }

        return $this->json($response);
    }
}
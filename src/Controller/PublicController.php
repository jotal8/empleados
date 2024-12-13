<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

use App\Services\JwtService;

/**
 * @Route("/api/public", name="api_public")
 */
class PublicController extends AbstractController
{
    /**
     * @Route("/login", name="login")
     */
    public function login(): JsonResponse
    {
        $token = JwtService::create([
            'nombre' => 'Julian',
            'apellido' => 'Otalvaro'
        ], 30);

        return $this->json([
            'message' => 'Login exitoso!!',
            'success' => true,
            'token' => $token
        ]);
    }
}
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/test", name="api_test")
 */
class TestController extends AbstractController
{
    /**
     * @Route("/test1", name="test_1")
     */
    public function app_test(): JsonResponse
    {
        return $this->json([
            'message' => 'test',
            'success' => true,
            'data' => [
                'algo!'
            ]
        ]);
    }
}
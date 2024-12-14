<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Services\UsuarioService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Connection;
use App\Entity\Usuario;
use Exception;


/**
 * @Route("/api/usuario", name="api_usuario")
 */
class UsuarioController extends AbstractController
{
    /**
     * 
     * Crea un usuario nuevo si se envian los parametros requeridos correctamente
     * 
     * @param Connection $Connection
     * @param Request $Request
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     * 
     * @Route("", name="create_usuario", methods={"POST"})
     */
    public function createUsuario(
        Connection $Connection,
        Request $Request,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $response = [
            'message' => '',
            'success' => false
        ];

        try{
            $email = $Request->get('correo');
            if(!$email){
                throw new Exception('El correo es requerido para la creacion del usuario!');
            }

            $UsuarioRepository = $entityManager->getRepository(Usuario::class);
            $Usuario = $UsuarioRepository->findByEmail($email);

            if($Usuario){
                throw new Exception('El usuario ya se encuentra registrado!');
            }

            $attributes = $UsuarioRepository->processRequest($Request->request->all());
            $Connection->insert('usuario', $attributes);

            $response['message'] = 'Se ha creado el usuario correctamente!';
            $response['success'] = true;
        }catch(throwable $th){
            $response['message'] = $th->getMessage();
        }

        return $this->json($response);
    }
}
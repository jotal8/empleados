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
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
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
            'success' => false,
            'emailSent'   => false
        ];

        $Connection->beginTransaction();

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

            $response['emailSent'] = $this->callEmailService(
                "{$attributes['nombres']} {$attributes['apellidos']}", 
                $attributes['password'],
                $Request->headers->get('Authorization')
            );

            $Connection->commit();
            $response['message'] = 'Se ha creado el usuario correctamente!';
            $response['success'] = true;
        }catch(throwable $th){
            $Connection->rollBack();
            $response['message'] = $th->getMessage();
        }

        return $this->json($response);
    }

    private function callEmailService($name, $password, $token)
    {
        $Client = new Client();

        try {
            $headers = [
                'Authorization' => $token
            ];

            $params = [
                'name' => $name,
                'password' => $password
            ];

            $clientRequest = $Client->request('POST', 'email-service:5000/sendEmail', [
                'verify'      => false,
                'headers'     => $headers,
                'form_params' => $params
            ]);

            $response = $clientRequest->getBody();
            $responseData = json_decode($response);
        }catch(throwable $th){
            return false;
            dd($th);
        }

        return $responseData->success;
    }
}
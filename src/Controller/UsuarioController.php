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
use App\Services\CallEmailService;
use Exception;


/**
 * @Route("/api/usuario", name="api_usuario")
 */
class UsuarioController extends AbstractController
{

    /**
     * 
     * Consulta los empleados registrados en el sistema
     * 
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     * 
     * @Route("", name="usuarios", methods={"GET"})
     */
    public function usuarios(
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $response = [
            'message' => '',
            'success' => false,
            'data'    => []
        ];

        try{
            $UsuarioRepository = $entityManager->getRepository(Usuario::class);
            $UsuarioList = $UsuarioRepository->findAll();

            $list = [];

            foreach($UsuarioList as $Usuario){
                $list[] = [
                    'nombres'   =>  $Usuario->getNombres(),
                    'apellidos' =>  $Usuario->getApellidos(),
                    'cargo'     =>  $Usuario->getCargo(),
                    'id'        =>  $Usuario->getId(),
                    'nacimiento'=>  $Usuario->getFechaNacimiento()->format('Y-m-d'),
                    'correo'    =>  $Usuario->getCorreo(),
                    'rol'       => $Usuario->getRol()
                ];
            }

            $response['data'] = $list;
            $response['message'] = 'Se han consultado los empleados correctamente!';
            $response['success'] = true;
        }catch(throwable $th){
            $response['message'] = $th->getMessage();
        }

        return $this->json($response);
    }

    /**
     * 
     * Consulta  la informacion de un usuario
     * 
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     * 
     * @Route("/{id}", name="usuarioData", methods={"GET"})
     */
    public function usuarioData(
        $id,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $response = [
            'message' => '',
            'success' => false,
            'data'    => []
        ];

        try{
            $UsuarioRepository = $entityManager->getRepository(Usuario::class);
            $Usuario = $UsuarioRepository->findById($id);

            $data = [
                'nombres'   =>  $Usuario->getNombres(),
                'apellidos' =>  $Usuario->getApellidos(),
                'cargo'     =>  $Usuario->getCargo(),
                'nacimiento'=>  $Usuario->getFechaNacimiento()->format('Y-m-d'),
                'correo'    =>  $Usuario->getCorreo(),
                'id'        =>  $Usuario->getId()
            ];

            $response['data'] = $data;
            $response['message'] = 'Se han consultado los datos del empleado correctamente!';
            $response['success'] = true;
        }catch(throwable $th){
            $response['message'] = $th->getMessage();
        }

        return $this->json($response);
    }

    /**
     * 
     * Edita el usuario
     * 
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     * 
     * @Route("/{id}", name="editUser", methods={"PUT"})
     */
    public function editUser(
        Request $Request,
        $id,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $response = [
            'message' => '',
            'success' => false,
            'data'    => []
        ];

        try{
            $UsuarioRepository = $entityManager->getRepository(Usuario::class);
            $Usuario = $UsuarioRepository->findById($id);

            $nombres = $Request->get('nombres', $Usuario->getNombres());
            $apellidos = $Request->get('apellidos', $Usuario->getApellidos());
            $fecha_nacimiento = $Request->get('fecha_nacimiento');
            $correo = $Request->get('correo', $Usuario->getCorreo());
            $cargo = $Request->get('cargo', $Usuario->getCargo());

            $UsuarioRepository->editUser($id, [
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'fecha_nacimiento' => $fecha_nacimiento != ''  ?
                    $fecha_nacimiento : 
                    $Usuario->getFechaNacimiento()->format('Y-m-d'),
                'correo' => $correo,
                'cargo' => $cargo != ''  ?
                $cargo : 
                $Usuario->getCorreo(),
            ]);

            $response['message'] = 'Se ha editado el usuario correctamente!';
            $response['success'] = true;
        }catch(throwable $th){
            $response['message'] = $th->getMessage();
        }

        return $this->json($response);
    }

    /**
     * 
     * Elimina un usuario
     * 
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     * 
     * @Route("/{id}", name="deleteUsuario", methods={"DELETE"})
     */
    public function deleteUsuario(
        EntityManagerInterface $entityManager,
        int $id
    ): JsonResponse
    {
        $response = [
            'message' => '',
            'success' => false,
            'data'    => []
        ];

        try{
            $UsuarioRepository = $entityManager->getRepository(Usuario::class);
            $Usuario = $UsuarioRepository->deleteById($id);

            $response['message'] = 'Se ha eliminado el empleado correctamente!';
            $response['success'] = true;
        }catch(throwable $th){
            $response['message'] = $th->getMessage();
        }

        return $this->json($response);
    }

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
            'message'     => '',
            'success'     => false,
            'emailSent'   => ''
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

            $response['emailSent'] = $response['emailSent'] = CallEmailService::request(
                "{$attributes['nombres']} {$attributes['apellidos']}", 
                $attributes['password'],
                $attributes['correo'],
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
}
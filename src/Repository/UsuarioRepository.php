<?php

namespace App\Repository;

use App\Entity\Usuario;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use ReflectionClass;
use Exception;

/**
 * @extends ServiceEntityRepository<Usuario>
 *
 * @method Usuario|null find($id, $lockMode = null, $lockVersion = null)
 * @method Usuario|null findOneBy(array $criteria, array $orderBy = null)
 * @method Usuario[]    findAll()
 * @method Usuario[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UsuarioRepository extends ServiceEntityRepository
{
    public UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        ManagerRegistry $registry, 
        UserPasswordHasherInterface $passwordHasher
        )
    {
        $this->passwordHasher = $passwordHasher;

        parent::__construct($registry, Usuario::class);
    }

    public function add(Usuario $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Usuario $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Busca con la llave primaria el registro en la db
     */
    public function findById($value): ?Usuario
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.id = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * Busca por el Correo del usuario en la db
     */
    public function findByEmail($value): ?Usuario
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.correo = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * Obtiene los parametros desde el request que llega al controlador
     * procesa la llaves que solo corresponden a la entidad y retorna correctamente 
     * los datos procesados con password hasheado
     * 
     * @param array $request
     */
    public function processRequest(array $request): array
    {
        $props = $this->getProps();
        $attributes = array_intersect_key($request, array_flip($props));
        $Usuario = new Usuario;

        $newPassword = $this->generateNewPassword();

        $attributes['password'] = $this->passwordHasher->hashPassword(
            $Usuario,
            $newPassword
        );

        $attributes['estado'] = 1;
        $attributes['rol'] = 'empleado';

        return $attributes;
    }

    /**
     * genera un password random temporal
     * @return string
     */
    private function generateNewPassword(): string
    {
        $rand = str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!&%()/*#&.+!');
        return substr($rand, 0, 12);
    }

    /**
     * Busca las propiedades de la entidad para guardarlas desde array
     * @return array
     */
    private function getProps(): array
    {
        $reflector = new ReflectionClass('App\Entity\Usuario');
        $props = $reflector->getProperties();

        $list = [];

        foreach($props as $prop){
            if($prop->name != 'id'){
                $list[] = $prop->name;
            }
        }

        return $list;
    }

    /**
     * Busca todos los empleados creados
     * 
     * @return array
     */
    public function findAll(): ?array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.estado = 1')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Busca todos los empleados creados
     * 
     * @return array
     */
    public function deleteById(int $id): void
    {
        $newEmailIdentificator = sprintf(
            '%s_eliminado',
            rand(0,1000000000)
        );

        $this->createQueryBuilder('u')
            ->update()
            ->set('u.estado', 0)
            ->set('u.correo', ':identificator')
            ->where('u.id = :val')
            ->setParameter('val', $id)
            ->setParameter('identificator', $newEmailIdentificator)
            ->getQuery()
            ->execute();
    }

    /**
     * Funcion para editar un usuario
     * 
     * @param $id
     * @param $attributes
     */
    public function editUser($id, $attributes){
        $this->createQueryBuilder('u')
            ->update()
            ->set('u.nombres', ':nombres')
            ->set('u.apellidos', ':apellidos')
            ->set('u.correo', ':correo')
            ->set('u.fecha_nacimiento', ':fecha_nacimiento')
            ->set('u.cargo', ':cargo')
            ->where('u.id = :val')
            ->setParameter('nombres', $attributes['nombres'])
            ->setParameter('apellidos', $attributes['apellidos'])
            ->setParameter('correo', $attributes['correo'])
            ->setParameter('fecha_nacimiento', $attributes['fecha_nacimiento'])
            ->setParameter('cargo', $attributes['cargo'])
            ->setParameter('val', $id)
            ->getQuery()
            ->execute();
    }
}

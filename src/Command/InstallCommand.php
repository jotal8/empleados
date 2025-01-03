<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Services\GlobalContainer;
use Symfony\Component\Dotenv\Dotenv;
use App\Entity\Usuario;
use DateTime;

/**
 * Comando que instala la informacion inicial de la aplicacion en la base de datos
 */
class InstallCommand extends Command
{
    protected static $defaultName = 'app:install:initialData';
    protected static $defaultDescription = 'Este comando instala la informacion inicial necesaria en la db para funcionar la app';

    private UserPasswordHasherInterface $passwordHasher;
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager,  
        UserPasswordHasherInterface $passwordHasher
    )
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        (new Dotenv())->bootEnv(dirname(__DIR__) . '/../.env');

        $io = new SymfonyStyle($input, $output);
        $Usuario = new Usuario();

        //Usuario administrador
        $email = 'unow@correo.com';
        $password = $this->passwordHasher->hashPassword(
            $Usuario,
            'holamundo'
        );

        if(!$this->createUser($Usuario, 'Administrador', 'N/A', $email, $password, 'Administrador')){
            $io->success('Ya se encuentra la instalacion inicial ejecutada!');
            return Command::INVALID;
        }

        //Usuario basico
        $Usuario2 = new Usuario();
        $email = 'usuario@correo.com';
        $password = $this->passwordHasher->hashPassword(
            $Usuario2,
            'holamundo'
        );

        if(!$this->createUser($Usuario2, 'Administrador', 'N/A', $email, $password, 'Usuario')){
            $io->success('Ya se encuentra la instalacion inicial ejecutada!');
            return Command::INVALID;
        }

        $io->success('Se ha instalado correctamente!');

        return Command::SUCCESS;
    }

    private function createUser(Usuario $Usuario, string $nombres, string $apellidos,string $email, string $password, string $rol): bool
    {
        $UsuarioRepository = $this->entityManager->getRepository(Usuario::class);
        $hasUser = $UsuarioRepository->findByEmail($email);

        if($hasUser){
            return false;
        }

        $Usuario->setNombres('Administrador');
        $Usuario->setApellidos('N/A');
        $Usuario->setCorreo($email);
        $Usuario->setEstado(1);
        $Usuario->setPassword($password);
        $Usuario->setCargo('System');
        $Usuario->setRol($rol);

        $Date = new DateTime();
        $Usuario->setFechaNacimiento($Date);

        $UsuarioRepository->add($Usuario, true);

        return true;
    }
}

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

        $email = 'administrador@correo.com';
        $password = $this->passwordHasher->hashPassword(
            $Usuario,
            'holamundo'
        );

        $UsuarioRepository = $this->entityManager->getRepository(Usuario::class);
        $hasUser = $UsuarioRepository->findByEmail($email);

        if($hasUser){
            $io->success('Ya se encuentra la instalacion inicial ejecutada!');

            return Command::INVALID;
        }

        $Usuario->setNombres('Administrador');
        $Usuario->setApellidos('N/A');
        $Usuario->setCorreo($email);
        $Usuario->setEstado(1);
        $Usuario->setPassword($password);
        $Usuario->setRol('Administrador');

        $UsuarioRepository->add($Usuario, true);

        $io->success('Se ha instalado correctamente!');

        return Command::SUCCESS;
    }
}

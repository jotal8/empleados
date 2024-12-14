<?php


namespace App\Services;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Path;

class GlobalContainer
{
    /**
     * ContainerInterface
     */
    private static ContainerInterface $container;

    /**
     * define el contenedor
     *
     * @param ContainerInterface $container
     */
    public static function setContainer(ContainerInterface $container)
    {
        self::$container = $container;
        $_SERVER["ROOT_PATH"] = sprintf("%s/", $container->getParameter('kernel.project_dir'));
    }

    /**
     * Obtiene la ruta raiz sin el "/" al final,
     * si $aditionalPath se asigna quitara el "/" al final
     *
     * @param string $aditionalPath
     * @return string
     */
    public static function getRootPath(string $aditionalPath = ''): string
    {
        return Path::canonicalize(sprintf(
            "%s/%s",
            self::getContainer()->getParameter('kernel.project_dir'),
            $aditionalPath
        ));
    }

    public static function getPublicPath(string $aditionalPath = ''): string
    {
        return self::getRootPath('public/' . $aditionalPath);
    }

    /**
     * obtiene el contenedor
     *
     * @return ContainerInterface
     */
    public static function getContainer(): ContainerInterface
    {
        return self::$container;
    }
}
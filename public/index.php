<?php

use App\Kernel;
use App\Services\GlobalContainer;
use Symfony\Component\Dotenv\Dotenv;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');

return function (array $context) {
    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
    $kernel->boot();
    GlobalContainer::setContainer($kernel->getContainer());

    return $kernel;
};
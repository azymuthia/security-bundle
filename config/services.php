<?php

declare(strict_types=1);

use Azymuthia\SecurityBundle\Security\JwtEventSubscriber;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $config): void {
    $services = $config->services();

    // Defaults: autowire & autoconfigure for bundle services, private by default
    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->private()
    ;

    $services->set(JwtEventSubscriber::class);
};

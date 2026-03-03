<?php

declare(strict_types=1);

use Azymuthia\SecurityBundle\Security\JwtEventSubscriber;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $config): void {
    $services = $config->services();

    // Defaults: autowire & autoconfigure for bundle services, private by default
    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->private()
    ;

    // Register the JWT event subscriber
    $services->set(JwtEventSubscriber::class)
        ->public()
        ->tag('kernel.event_subscriber')
        // ensure the iterable argument receives tagged iterator; CompilerPass also enforces this
        ->arg('$appUserRepositories', new TaggedIteratorArgument('azymuthia.security.app_user_repository', null, null, true))
    ;
};

<?php

declare(strict_types=1);

namespace Aquila\SecurityBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Aquila\SecurityBundle\Contract\AppUserRepositoryInterface;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class AquilaSecurityExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        // Enable autoconfiguration for AppUserRepositoryInterface implementations
        $container->registerForAutoconfiguration(AppUserRepositoryInterface::class)
            ->addTag('aquila.security.app_user_repository')
        ;

        // Load services from PHP config file if present (optional, minimal bundle keeps it empty)
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        try {
            $loader->load('services.php');
        } catch (\Throwable) {
            // no-op for minimal skeleton
        }
    }

    public function getAlias(): string
    {
        return 'aquila_security';
    }
}

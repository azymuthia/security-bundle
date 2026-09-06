<?php

declare(strict_types=1);

namespace Azymuthia\SecurityBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Throwable;

final class AzymuthiaSecurityExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('azymuthia_security.exit_url', $config['exit_url']);
        $container->setParameter('azymuthia_security.logout.endpoint', $config['logout']['endpoint']);

        // Load services from the PHP config file if present (optional, minimal bundle keeps it empty)
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config'));

        try {
            $loader->load('services.php');
        } catch (Throwable) {
            // no-op for minimal skeleton
        }
    }

    public function getAlias(): string
    {
        return 'azymuthia_security';
    }
}

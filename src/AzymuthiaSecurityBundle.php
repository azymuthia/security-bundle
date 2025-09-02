<?php

declare(strict_types=1);

namespace Azymuthia\SecurityBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Azymuthia\SecurityBundle\DependencyInjection\AzymuthiaSecurityExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Azymuthia\SecurityBundle\DependencyInjection\Compiler\AppUserAutowirePass;

final class AzymuthiaSecurityBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new AppUserAutowirePass());
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new AzymuthiaSecurityExtension();
    }
}

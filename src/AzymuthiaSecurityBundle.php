<?php

declare(strict_types=1);

namespace Azymuthia\SecurityBundle;

use Azymuthia\SecurityBundle\DependencyInjection\AzymuthiaSecurityExtension;
use Azymuthia\SecurityBundle\DependencyInjection\Compiler\AppUserAutowirePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

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

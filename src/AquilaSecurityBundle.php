<?php

declare(strict_types=1);

namespace Aquila\SecurityBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Aquila\SecurityBundle\DependencyInjection\AquilaSecurityExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Aquila\SecurityBundle\DependencyInjection\Compiler\AppUserAutowirePass;

final class AquilaSecurityBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new AppUserAutowirePass());
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new AquilaSecurityExtension();
    }
}

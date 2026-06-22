<?php

declare(strict_types=1);

namespace Azymuthia\SecurityBundle;

use Azymuthia\SecurityBundle\Contract\AppUserRepositoryInterface;
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

        $container
            ->registerForAutoconfiguration(AppUserRepositoryInterface::class)
            ->addTag('azymuthia.security.app_user_repository')
        ;

        $container->addCompilerPass(new AppUserAutowirePass());
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new AzymuthiaSecurityExtension();
    }
}

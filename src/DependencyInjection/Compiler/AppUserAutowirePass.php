<?php

declare(strict_types=1);

namespace Azymuthia\SecurityBundle\DependencyInjection\Compiler;

use Azymuthia\SecurityBundle\Security\JwtEventSubscriber;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class AppUserAutowirePass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(JwtEventSubscriber::class) && !$container->hasAlias(JwtEventSubscriber::class)) {
            return; // Subscriber not registered yet
        }

        $definition = $container->findDefinition(JwtEventSubscriber::class);

        // Ensure the second constructor argument (iterable repositories) receives tagged iterator.
        // We use named argument if available otherwise index 1 (0-based: [urls, appUserRepositories]).
        $tagged = new TaggedIteratorArgument('azymuthia.security.app_user_repository', null, null, true);

        // Try set by argument name first
        if (method_exists($definition, 'setArgument')) {
            $definition->setArgument('$appUserRepositories', $tagged);
        } else {
            $definition->replaceArgument(1, $tagged);
        }
    }
}

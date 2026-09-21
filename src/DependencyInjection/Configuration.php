<?php

declare(strict_types=1);

namespace Azymuthia\SecurityBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('azymuthia_security');

        $treeBuilder->getRootNode()
            ->children()
            ->scalarNode('exit_url')->defaultValue('%env(SECURITY_EXIT_URL)%')->cannotBeEmpty()->end()
            ->scalarNode('bearer_cookie_name')->defaultValue('%env(BEARER_COOKIE_NAME)%')->cannotBeEmpty()->end()
            ->scalarNode('deployment_env')->defaultValue('%env(APP_DEPLOYMENT_ENV)%')->cannotBeEmpty()->end()
            ->end()
        ;

        return $treeBuilder;
    }
}

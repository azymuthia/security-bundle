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
            ->scalarNode('exit_url')->isRequired()->cannotBeEmpty()->end()
            ->arrayNode('logout')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('endpoint')->defaultValue('/api/cross-app/logout')->end()
            ->end()
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}

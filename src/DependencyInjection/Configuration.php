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
                // Keep configuration minimal for now; extend later as needed
            ->end()
        ;

        return $treeBuilder;
    }
}

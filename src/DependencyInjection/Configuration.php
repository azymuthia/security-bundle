<?php

declare(strict_types=1);

namespace Aquila\SecurityBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('aquila_security');

        $treeBuilder->getRootNode()
            ->children()
                // Keep configuration minimal for now; extend later as needed
            ->end()
        ;

        return $treeBuilder;
    }
}

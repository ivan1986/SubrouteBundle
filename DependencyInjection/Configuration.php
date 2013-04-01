<?php

namespace Ivan1986\SubrouteBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('subroute');

        $rootNode
            ->isRequired()
            ->useAttributeAsKey('name')
            ->requiresAtLeastOneElement()
            ->prototype('array')
                ->beforeNormalization()
                ->ifString()
                    ->then(function($v) { return array('file'=> $v); })
                ->end()
                ->children()
                    ->scalarNode('file')->isRequired()->end()
                    ->scalarNode('rewrite')->defaultFalse()->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}

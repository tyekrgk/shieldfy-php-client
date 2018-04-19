<?php
namespace Shieldfy\Extensions\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('shieldfy');

        $rootNode
            ->children()
                ->scalarNode('app_key')->defaultValue('')->end()
                ->scalarNode('app_secret')->defaultValue('')->end()
                ->scalarNode('debug')->defaultValue('')->end()
                ->scalarNode('action')->defaultValue('')->end()
                ->scalarNode('blockPage')->defaultValue('')->end()
                ->arrayNode('headers')->end()
                ->arrayNode('disable')->end()
            ->end();

        return $treeBuilder;
    }
}
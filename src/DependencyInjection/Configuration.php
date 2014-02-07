<?php
namespace Uecode\Bundle\QPushBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('uecode_qpush');

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('cache_service')
                    ->defaultNull()
                ->end()
                ->append($this->getProvidersNode())
                ->append($this->getQueuesNode())
            ->end();

        return $treeBuilder;
    }

    private function getProvidersNode()
    {
        $treeBuilder    = new TreeBuilder();
        $node           = $treeBuilder->root('providers');

        $node
            ->requiresAtLeastOneElement()
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->children()
                    ->scalarNode('provider_service')
                        ->isRequired()
                        ->info('The Service Id of the Service used in the provider')
                    ->end()
                ->end()
            ->end();
        return $node;
    }

    private function getQueuesNode()
    {
        $treeBuilder    = new TreeBuilder();
        $node           = $treeBuilder->root('queues');

        $node
            ->requiresAtLeastOneElement()
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->children()
                    ->scalarNode('provider')
                        ->isRequired()
                        ->info('The name of the Queue Provider to use')
                    ->end()
                    ->arrayNode('options')
                        ->children()
                            ->booleanNode('push_notifications')
                                ->defaultFalse()
                                ->info('Whether notifications are sent to the subscribers')
                            ->end()
                            ->append($this->getSubscribersNode())
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $node;
    }

    private function getSubscribersNode()
    {
        $treeBuilder    = new TreeBuilder();
        $node           = $treeBuilder->root('subscribers');

        $node
            ->prototype('array')
                ->children()
                    ->scalarNode('endpoint')
                        ->info('The url or email address to notify')
                        ->example('http://foo.bar/qpush/')
                    ->end()
                    ->enumNode('protocol')
                        ->values(['email', 'http', 'https'])
                        ->info('The endpoint type')
                        ->example('http')
                    ->end()
                ->end()
            ->end();

        return $node;
    }
}

<?php

/**
 * Copyright 2014 Underground Elephant
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @package     qpush-bundle
 * @copyright   Underground Elephant 2014
 * @license     Apache License, Version 2.0
 */

namespace Uecode\Bundle\QPushBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
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
                ->booleanNode('logging_enabled')
                    ->defaultTrue()
                ->end()
                ->append($this->getProvidersNode())
                ->append($this->getQueuesNode())
            ->end()
        ;

        return $treeBuilder;
    }

    private function getProvidersNode()
    {
        $treeBuilder    = new TreeBuilder();
        $node           = $treeBuilder->root('providers');
        $requirements   = [
            'aws' => ['key', 'secret'],
            'ironmq' => ['token', 'project_id'],
            'sync' => [],
        ];

        $node
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->treatNullLike([])
                ->children()
                    ->enumNode('driver')
                        ->isRequired()
                        ->values(array_keys($requirements))
                    ->end()
                    // IronMQ
                    ->scalarNode('token')->end()
                    ->scalarNode('project_id')->end()
                    ->enumNode('host')
                        ->defaultValue('mq-aws-us-east-1')
                        ->values([
                            'mq-aws-us-east-1',
                            'mq-aws-eu-west-1',
                            'mq-rackspace-ord',
                            'mq-rackspace-lon',
                        ])
                    ->end()
                    ->scalarNode('port')
                        ->defaultValue('443')
                    ->end()
                    ->scalarNode('api_version')
                        ->defaultValue(1)
                    ->end()
                    // AWS
                    ->scalarNode('key')->end()
                    ->scalarNode('secret')->end()
                    ->scalarNode('region')
                        ->defaultValue('us-east-1')
                    ->end()
                ->end()

                ->validate()
                ->always()
                ->then(function (array $provider) use ($node, $requirements) {
                    foreach ($requirements[$provider['driver']] as $requirement) {
                        if (empty($provider[$requirement])) {
                            throw new \InvalidArgumentException(
                                sprintf('%s queue providers must have a %s; none provided', $provider['driver'], $requirement)
                            );
                        }
                    }

                    return $provider;
                })
            ->end()
        ;

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
                        ->info('The Queue Provider to use')
                    ->end()
                    ->arrayNode('options')
                        ->children()
                            ->scalarNode('queue_name')
                                ->defaultNull()
                                ->info('The actual name of the queue')
                            ->end()
                            ->booleanNode('push_notifications')
                                ->defaultFalse()
                                ->info('Whether notifications are sent to the subscribers')
                            ->end()
                             ->scalarNode('notification_retries')
                                 ->defaultValue(3)
                                 ->info('How many attempts the Push Notifications are retried if the Subscriber returns an error')
                                 ->example(3)
                             ->end()
                             ->scalarNode('message_delay')
                                 ->defaultValue(0)
                                 ->info('How many seconds before messages are inititally visible in the Queue')
                                 ->example(0)
                             ->end()
                             ->scalarNode('message_timeout')
                                 ->defaultValue(30)
                                 ->info('How many seconds the Queue hides a message while its being processed')
                                 ->example(30)
                             ->end()
                             ->scalarNode('message_expiration')
                                 ->defaultValue(604800)
                                 ->info('How many seconds a message is kept in Queue, the default is 7 days (604800 seconds)')
                                 ->example(604800)
                             ->end()
                             ->scalarNode('messages_to_receive')
                                 ->defaultValue(1)
                                 ->info('Max amount of messages to receive at once - an event will be fired for each individually')
                                 ->example(1)
                             ->end()
                             ->scalarNode('receive_wait_time')
                                 ->defaultValue(3)
                                 ->info('How many seconds to Long Poll when requesting messages - if supported')
                                 ->example(3)
                             ->end()
                            ->append($this->getSubscribersNode())
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

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
            ->end()
        ;

        return $node;
    }
}

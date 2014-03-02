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

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

/**
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class UecodeQPushExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('uecode_qpush.cache', $config['cache_service']);
        if (empty($config['cache_service'])) {
            $this->createFileCache($container);
        }

        $container->setParameter('uecode_qpush.queues', $config['queues']);
        $container->setParameter('uecode_qpush.providers', $config['providers']);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );

        $loader->load('services.yml');

        $cache      = $container->getDefinition('uecode_qpush.file_cache');
        $logger     = $container->getDefinition('uecode_qpush.logger');
        $registry   = $container->getDefinition('uecode_qpush.registry');

        foreach ($config['queues'] as $queue => $values) {
            // Adds logging property to queue options
            $values['options']['logging_enabled'] = $config['logging_enabled'];

            $provider = $values['provider'];
            $class = null;
            $client = null;
            switch ($provider) {
                case 'aws':
                    $class  = $container->getParameter('uecode_qpush.provider.aws');
                    $client = $this->createAwsClient(
                        $config['providers'][$provider],
                        $container
                    );
                    break;
                case 'ironmq':
                    $class  = $container->getParameter('uecode_qpush.provider.ironmq');
                    $client = $this->createIronMQClient(
                        $config['providers'][$provider],
                        $container
                    );
                    break;
                case 'rabbitmq':
                    $class  = $container->getParameter('uecode_qpush.provider.rabbitmq');
//                    $client = $this->x(
//
//                    )
            }

            $definition = new Definition(
                $class, [$queue, $values['options'], $client, $cache, $logger]
            );

            $name = sprintf('uecode_qpush.%s', $queue);

            $container->setDefinition($name, $definition)
                ->addTag('monolog.logger', ['channel' => 'qpush'])
                ->addTag(
                    'uecode_qpush.event_listener',
                    [
                        'event' => "{$queue}.on_notification",
                        'method' => "onNotification",
                        'priority' => 255
                    ]
                )
                ->addTag(
                    'uecode_qpush.event_listener',
                    [
                        'event' => "{$queue}.message_received",
                        'method' => "onMessageReceived",
                        'priority' => -255
                    ]
                )
            ;

            $registry->addMethodCall('addProvider', [$queue, new Reference($name)]);
        }
    }

    /**
     * Creates a definition for the AWS provider
     *
     * @param array            $config    A Configuration array for the client
     * @param ContainerBuilder $container The container
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @return Definition
     */
    private function createAwsClient($config, ContainerBuilder $container)
    {
        if (!$container->hasDefinition('uecode_qpush.provider.aws')) {

            if (!class_exists('Aws\Common\Aws')) {
                throw new \RuntimeException(
                    'You must require "aws/aws-sdk-php" to use the AWS provider.'
                );
            }

            // Validate the config
            if (empty($config['key']) || empty($config['secret'])) {
                throw new \InvalidArgumentException(
                    'The `key` and `secret` must be set in your configuration file to use the AWS Provider'
                );
            }

            $aws = new Definition('Aws\Common\Aws');
            $aws->setFactoryClass('Aws\Common\Aws');
            $aws->setFactoryMethod('factory');
            $aws->setArguments([
                [
                    'key'      => $config['key'],
                    'secret'   => $config['secret'],
                    'region'   => $config['region']
                ]
            ]);

            $container->setDefinition('uecode_qpush.provider.aws', $aws)
                ->setPublic(false);

        } else {
            $aws = $container->getDefinition('uecode_qpush.provider.aws');
        }

        return $aws;
    }

    /**
     * Creates a definition for the IronMQ provider
     *
     * @param array            $config    A Configuration array for the provider
     * @param ContainerBuilder $container The container
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @return Definition
     */
    private function createIronMQClient($config, ContainerBuilder $container)
    {
        if (!$container->hasDefinition('uecode_qpush.provider.ironmq')) {

            if (!class_exists('IronMQ')) {
                throw new \RuntimeException(
                    'You must require "iron-io/iron_mq" to use the Iron MQ provider.'
                );
            }

            // Validate the config
            if (empty($config['token']) || empty($config['project_id'])) {
                throw new \InvalidArgumentException(
                    'The `token` and `project_id` must be properly set in your configuration file to use the IronMQ Provider'
                );
            }

            $ironmq = new Definition('IronMQ');
            $ironmq->setArguments([
                [
                    'token'         => $config['token'],
                    'project_id'    => $config['project_id']
                ]
            ]);

            $container->setDefinition('uecode_qpush.provider.ironmq', $ironmq)
                ->setPublic(false);

        } else {
            $ironmq = $container->getDefinition('uecode_qpush.provider.ironmq');
        }

        return $ironmq;
    }

    /**
     * Sets a Definition for PhpFileCache in the container
     *
     * @param ContainerBuilder $container The container
     */
    private function createFileCache(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('uecode_qpush.file_cache')) {
            $directory = $container->getParameter('kernel.cache_dir') . '/qpush/';
            $container->setDefinition(
                'uecode_qpush.file_cache',
                new Definition(
                    'Doctrine\Common\Cache\PhpFileCache',
                    [$directory, 'uecode.php']
                )
            )->setPublic(false);
        }
    }

    /**
     * Returns the Extension Alias
     *
     * @return string
     */
    public function getAlias()
    {
        return 'uecode_qpush';
    }
}

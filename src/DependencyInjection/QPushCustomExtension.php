<?php

namespace Uecode\Bundle\QPushBundle\DependencyInjection;

use Uecode\Bundle\QPushBundle\DependencyInjection\Configuration;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

class QPushCustomExtension extends Extension
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

        $registry = $container->getDefinition('uecode_qpush.registry');

        foreach ($config['queues'] as $queue => $options) {

            $provider = $options['provider'];
            switch($provider)
            {
                case 'aws':
                    $definition = $this->createAwsProvider($config['providers'][$provider], $container);
                    break;
                case 'ironmq':
                    $definition = $this->createIronMQProvider($config['providers'][$provider], $container);
                    break;
            }

            $name = sprintf('uecode_qpush.%s', $queue);
            $service = $container->setDefinition($name, $definition)
                ->setPublic(true)
                ->replaceArgument(0, $queue)
                ->replaceArgument(1, $options['options'])
                ->addTag(
                    'uecode_qpush.event_listener',
                    ['event' => "{$queue}.on_notification", 'method' => "onNotification", 'priority' => 255]
                )
                ->addTag(
                    'uecode_qpush.event_listener',
                    ['event' => "{$queue}.message_received", 'method' => "onMessage", 'priority' => -255 ]
                );

            $registry->addMethodCall('addProvider', [$queue, new Reference($name)]);
        }
    }

    /**
     * Creates a definition for the AWS provider
     *
     * @param array             $config     A Configuration array for the client
     * @param ContainerBuilder  $container  The container
     *
     * return Definition
     */
    private function createAwsProvider($config, ContainerBuilder $container)
    {
        if (!class_exists('Aws\Common\Aws')) {
            throw new \RuntimeException(
                'You must require "aws/aws-sdk-php" to use the AWS provider.'
            );
        }

        if (!$container->hasDefinition('uecode_qpush.provider.aws')) {

            // Validate the config
            if (empty($config['key']) || empty($config['secret'])) {
                throw new \InvalidArgumentException(
                    'The `key` and `secret` must be set in your configuration file to use the AWS Provider'
                );
            }

            $cache = $container->getDefinition('uecode_qpush.file_cache');

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

            $parameter  = $container->getParameter('uecode_qpush.provider.aws');
            $provider   = new Definition($parameter, [null, null, $cache, $aws]);
            $container
                        ->setDefinition('uecode_qpush.provider.aws', $provider)
                        ->setPublic(false);
        } else {
            $provider = $container->getDefinition('uecode_qpush.provider.aws');
        }

        return $provider;
    }

    /**
     * Creates a definition for the IronMQ provider
     *
     * @param array             $config     A Configuration array for the provider
     * @param ContainerBuilder  $container  The container
     *
     * return Definition
     */
    private function createIronMQProvider($config, ContainerBuilder $container)
    {
        if (!class_exists('IronMQ')) {
            throw new \RuntimeException(
                'You must require "iron-io/iron_mq" to use the Iron MQ provider.'
            );
        }

        if (!$container->hasDefinition('uecode_qpush.provider.ironmq')) {

            // Validate the config
            if (empty($config['token']) || empty($config['project_id'])) {
                throw new \InvalidArgumentException(
                    'The `token` and `project_id` must be properly set in your configuration file to use the IronMQ Provider'
                );
            }

            $cache = $container->getDefinition('uecode_qpush.file_cache');

            $ironMq = new Definition('IronMQ');
            $ironMq->setArguments([
                [
                    'token'         => $config['token'],
                    'project_id'    => $config['project_id']
                ]
            ]);

            $parameter  = $container->getParameter('uecode_qpush.provider.ironmq');
            $provider   = new Definition($parameter, [null, null, $cache, $ironMq]);
            $container
                        ->setDefinition('uecode_qpush.provider.ironmq', $provider)
                        ->setPublic(false);
        } else {
            $provider = $container->getDefinition('uecode_qpush.provider.ironmq');
        }

        return $provider;
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
            $fileCache = $container->setDefinition(
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

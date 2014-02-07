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
        $container->setParameter('uecode_qpush.queues', $config['queues']);
        $container->setParameter('uecode_qpush.providers', $config['providers']);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yml');

        $registry = $container->getDefinition('uecode_qpush.registry');
        $this->buildQueues($config, $container, $registry);
    }

    private function buildQueues(array $queues, ContainerBuilder $container, Definition $registry)
    {

        $directory = $container->getParameter('kernel.cache_dir') . '/qpush/';
        $fileCache = $container->setDefinition(
            'uecode_qpush.file_cache',
            new Definition('Doctrine\Common\Cache\PhpFileCache',[$directory, 'uecode.php'])
        )->setPublic(false);

        foreach ($queues['queues'] as $queue => $config) {
            $name = sprintf('uecode_qpush.%s', $queue);

            $provider = sprintf('uecode_qpush.provider.%s', $config['provider']);
            if (!$container->hasParameter($provider)) {
                throw new \InvalidArgumentException(
                    sprintf('Invalid "%s" provider on "%s" queue.', $config['provider'], $queue)
                );
            }

            $provider = $container->getParameter($provider);

            $interfaces = class_implements($provider);
            $interface = 'Uecode\Bundle\QPushBundle\Queue\QueueProviderInterface';
            if (!isset($interfaces[$interface])) {
                throw new \Exception(
                    sprintf('The class %s must implement the %s', $provider, $interface)
                );
            }

            $definition = new Definition($provider, [$queue, $config['options'], $fileCache]);

            $service = $container->setDefinition($name, $definition)
                ->addTag(
                    'uecode_qpush.event_listener',
                    ['event' => "{$queue}.notify", 'method' => "onNotify", 'priority' => 255]
                )
                ->addTag(
                    'uecode_qpush.event_listener',
                    ['event' => "{$queue}.subscription", 'method' => "onSubscription", 'priority' => 255]
                )
                ->addTag(
                    'uecode_qpush.listener_event',
                    ['event' => "{$queue}.message", 'method' => "onMessage", 'priority' => -255 ]
                );

            $registry->addMethodCall('addQueue', [$queue, new Reference($name)]);
        }
    }

    public function getAlias()
    {
        return 'uecode_qpush';
    }
}

<?php

namespace Uecode\Bundle\QPushBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use Uecode\Bundle\QPushBundle\DependencyInjection\Configuration;

class QPushCustomExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('aws.credentials', $config['aws_credentials']);
        $container->setParameter('uecode_qpush.queues', $config['queues']);
        $container->setParameter('uecode_qpush.cache', $config['cache_service_id']);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yml');

        $registry = $container->getDefinition('uecode_qpush.registry');
        $this->buildQueues($config['queues'], $container, $registry);
    }

    private function buildQueues(array $queues, ContainerBuilder $container, Definition $registry)
    {
        $prefix = 'uecode_qpush';
        foreach ($queues as $queue => $options) {
            $name = $prefix . '.' . $queue;
            $service = $container->setDefinition(
                $name,
                new DefinitionDecorator('uecode_qpush.service')
            )
                ->replaceArgument(0, $queue)
                ->replaceArgument(1, $options)
                ->addTag(
                    'uecode_qpush.event_listener',
                    [
                        'event' => "{$queue}.notify",
                        'priority' => 255
                    ]
                )
                ->addTag(
                    'uecode_qpush.event_listener',
                    [
                        'event' => "{$queue}.subscription",
                        'priority' => 255
                    ]
                )
                ->addTag(
                    'uecode_qpush.listener_event',
                    [
                        'event' => "{$queue}.message",
                        'priority' => -255
                    ]
                );

            $registry->addMethodCall('addQueue', [$queue, new Reference($name)]);
        }
    }

    public function getAlias()
    {
        return 'uecode_qpush';
    }
}

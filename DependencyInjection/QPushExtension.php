<?php

namespace Uecode\Bundle\QPushBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;
use QPush\Bundle\QPushBundle\DependencyInjection\Configuration;

class QPushExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('aws.credentials', $config['aws_creds']);
        $container->setParameter('qpush.queues', $config['queues']);

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
        $listeners = [];
        foreach($queues as $queue => $options) {
            $name = $prefix . '.' . $queue;
            $service = $this->container->setDefinition(
                $name, 
                new DefinitionDecorator('uecode_qpush.service')
            )
                ->replaceArgument(0, $queue)
                ->replaceArgument(1, $options)
                ->addTag(
                    'uecode_qpush.event_listener.' . $name, 
                    [ 
                        'event' => 'uecode_qpush.notification_received',
                        'priority' => 255
                    ]
                )
                ->addTag(
                    'uecode_qpush.event_listener.' . $name, 
                    [ 
                        'event' => 'uecode_qpush.subscription_change',
                        'priority' => 255
                    ]
                )
                ->addTag(
                    'uecode_qpush.event_listener.' . $name, 
                    [ 
                        'event' => 'uecode_qpush.message_retrieved',
                        'priority' => -255
                    ]
                );
            
            $registry->addMethodCall('addQueue', [$service]);
            $listeners[] = $name;
        }
        $container->setParameter('uecode_qpush.event_listeners', $listeners);
    }
}

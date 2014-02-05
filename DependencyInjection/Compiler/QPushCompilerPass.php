<?php

namespace Uecode\Bundle\QPushBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use InvalidArgumentException;

/**
 *  Borrows/modifies code from RegisterListenerPass to register the custom / dynamic
 *  events for QPush
 *
 *  @see Symfony\Component\HttpKernel\DependencyInjection\RegisterListenerPass
 */
class QPushCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $queues = $container->getParameter('uecode_qpush.queues');
        $cache  = $container->getParameter('uecode_qpush.cache');

        $prefix = 'uecode_qpush';
        foreach (array_keys($queues) as $queue) {
            $name = $prefix . '.' . $queue;
            $definition = $container->getDefinition($name);
            $cache = $this->getCache($cache, $container);
            $definition->replaceArgument(2, $cache);
        }

        if (!$container->hasDefinition('event_dispatcher')) {
            return;
        }

        $listeners = $container->getParameter('uecode_qpush.event_listeners');
        $definition = $container->getDefinition('event_dispatcher');

        foreach ($listeners as $listener) {

            $listenerTag    = 'uecode_qpush.listener.' . $listener;
            $subscriberTag  = 'uecode_qpush.subscriber.' . $listener;
            foreach ($container->findTaggedServiceIds($listenerTag) as $id => $events) {

                $def = $container->getDefinition($id);

                if (!$def->isPublic()) {
                    throw new InvalidArgumentException(sprintf('The service "%s" must be public as event listeners are lazy-loaded.', $id));
                }

                if ($def->isAbstract()) {
                    throw new InvalidArgumentException(sprintf('The service "%s" must not be abstract as event listeners are lazy-loaded.', $id));
                }

                foreach ($events as $event) {
                    $priority = isset($event['priority']) ? $event['priority'] : 0;

                    if (!isset($event['event'])) {
                        throw new InvalidArgumentException(sprintf('Service "%s" must define the "event" attribute on "%s" tags.', $id, $listenerTag));
                    }

                    if (!isset($event['method'])) {
                        $event['method'] = 'on'.preg_replace_callback(array(
                            '/(?<=\b)[a-z]/i',
                            '/[^a-z0-9]/i',
                        ), function ($matches) { return strtoupper($matches[0]); }, str_replace('uecode_qpush.', '', $event['event']));
                        $event['method'] = preg_replace('/[^a-z0-9]/i', '', $event['method']);
                    }

                    $definition->addMethodCall('addListenerService', array($event['event'], array($id, $event['method']), $priority));
                }
            }

            foreach ($container->findTaggedServiceIds($subscriberTag) as $id => $attributes) {
                $def = $container->getDefinition($id);
                if (!$def->isPublic()) {
                    throw new InvalidArgumentException(sprintf('The service "%s" must be public as event subscribers are lazy-loaded.', $id));
                }

                // We must assume that the class value has been correctly filled, even if the service is created by a factory
                $class = $def->getClass();

                $refClass = new \ReflectionClass($class);
                $interface = 'Symfony\Component\EventDispatcher\EventSubscriberInterface';
                if (!$refClass->implementsInterface($interface)) {
                    throw new InvalidArgumentException(sprintf('Service "%s" must implement interface "%s".', $id, $interface));
                }

                $definition->addMethodCall('addSubscriberService', array($id, $class));
            }
        }
    }

    /**
     * @param string           $cache     Optional Cache Service Id
     * @param ContainerBuilder $container Container from Symfony
     *
     * @return Reference|Definition
     */
    private function getCache($cache, ContainerBuilder $container)
    {
        if (null !== $cache) {
            if (!$container->hasDefinition($cache)) {
                throw new InvalidArgumentException(
                    sprintf(
                        "The service \"%s\" does not exist.", $cache
                    )
                );
            }

            return new Reference($cache);
        }

        $directory = $container->getParameter('kernel.cache_dir') . '/qpush/';
        $extension = 'uecode.php';

        return $container->setDefinition(
            'uecode_qpush.file_cache',
            new Definition(
                'Doctrine\Common\Cache\PhpFileCache',
                [$directory, $extension]
            )
        )->setPublic(false);
    }
}

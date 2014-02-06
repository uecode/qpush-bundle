<?php

namespace Uecode\Bundle\QPushBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use InvalidArgumentException;

class QPushCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $cache      = $container->getParameter('uecode_qpush.cache');
        $queues     = $container->getParameter('uecode_qpush.queues');

        foreach ($queues as $queue => $optons) {
            $name = sprintf('uecode_push.%s', $queue);

            $definition = $container->getDefinition($name);

            if ($cache = $this->getCache($cache, $container)) {
                $definition->replaceArgument(2, $cache);
            }

            if (isset($options['provider_service'])) {
                $service = $options['provider_service'];
                if (!$container->hasDefinition($service)) {
                    throw new InvalidArgumentException(
                        sprintf("The service \"%s\" does not exist.", $service)
                    );
                }
                $definition->addMethodCall('setService', [new Reference($service)]);
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
                    sprintf("The service \"%s\" does not exist.", $cache)
                );
            }

            return new Reference($cache);
        }

        return false;
    }
}

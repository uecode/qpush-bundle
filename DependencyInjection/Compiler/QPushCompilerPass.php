<?php

namespace Uecode\Bundle\QPushBundle\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;
use InvalidArgumentException;

class QPushCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $queues = $container->getParameter('qpush.queues');
       
        $prefix = 'uecode_qpush';
        foreach ($queues as $queue => $options) {
            $name = $prefix . '.' . $queue;
            $definition = $container->getDefinition($queue);
            $cache = $this->getCache($options, $container);
            $definition->replaceArgument(2, $cache);
        }
    }

    /**
     * @param array             $options    Options for the queue
     * @param ContainerBuilder  $container  Container from Symfony
     *
     * @return Reference|Definition
     */
    private function getCache(array $options, ContainerBuilder $container)
    {
        $cacheServiceId = $options['cache_service_id'];

        if (null !== $cacheServiceId) {
            if (!$container->hasDefinition($cacheServiceId)) {
                throw new InvalidArgumentException(
                    sprintf(
                        "The service \"%s\" does not exist.", $cacheServiceId
                    )
                );
            }

            return new Reference($cacheServiceId);
        }

        $directory = $container->getParameter('kernel.cache_dir') . '/qpush/';
        $extension = 'uecode.php';

        return $container->setDefinition(
            'uecode_qpush.file_cache',
            new Definition(
                'Doctrine\Common\Cache\FileCache', 
                [$directory, $extension]
            )
        )->setPublic(false);
    }
}

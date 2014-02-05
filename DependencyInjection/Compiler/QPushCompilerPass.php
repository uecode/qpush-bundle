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
        $queues = $container->getParameter('uecode_qpush.queues');
        $cache  = $container->getParameter('uecode_qpush.cache');

        $prefix = 'uecode_qpush';
        foreach (array_keys($queues) as $queue) {
            $name = $prefix . '.' . $queue;
            $definition = $container->getDefinition($name);
            $cache = $this->getCache($cache, $container);
            $definition->replaceArgument(2, $cache);
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

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

namespace Uecode\Bundle\QPushBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class QPushCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container Container from Symfony
     *
     * @throws \InvalidArgumentException
     */
    public function process(ContainerBuilder $container)
    {
        $cache = $container->getParameter('uecode_qpush.cache');

        if (!empty($cache)) {
            if (!$container->hasDefinition($cache)) {
                throw new \InvalidArgumentException(
                    sprintf("The \"%s\" service is not defined!", $cache)
                );
            }

            $cache  = $container->getDefinition($cache);
            $queues = $container->getParameter('uecode_qpush.queues');

            foreach ($queues as $queue) {
                $name       = sprintf('uecode_qpush.%s', $queue);
                $definition = $container->getDefinition($name);

                $definition->replaceArgument(3, $cache);
            }
        }
    }
}

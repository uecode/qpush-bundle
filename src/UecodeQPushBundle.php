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

namespace Uecode\Bundle\QPushBundle;

use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Uecode\Bundle\QPushBundle\DependencyInjection\UecodeQPushExtension;

/**
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class UecodeQPushBundle extends Bundle
{
    /**
     * {@inlineDoc}
     */
    public function __construct()
    {
        // Setting extension to bypass alias convention check
        $this->extension = new UecodeQPushExtension();
    }

    /**
     * Adds the Compiler Passes for the QPushBundle
     *
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(
            new RegisterListenersPass(
                'event_dispatcher',
                'uecode_qpush.event_listener',
                'uecode_qpush.event_subscriber'
            )
        );
    }
}

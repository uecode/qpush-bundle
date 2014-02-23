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

namespace Uecode\Bundle\QPushBundle\Provider;

/**
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class ProviderRegistry
{
    /**
     * All services tagged with `uecode_qpush.receive`
     * @var array
     */
    private $queues;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->queues = [];
    }

    /**
     * Adds a Listener to the chain based on priority
     *
     * @param string                 $name    The name of the Queue
     * @param ProviderInterface $service The QueueProvider
     */
    public function addProvider($name, ProviderInterface $service)
    {
        $this->queues[$name] = $service;
    }

    /**
     * Returns the Queues
     *
     * @return array
     */
    public function all()
    {
        return $this->queues;
    }

    /**
     * Checks whether a Queue Provider exists in the Regisitry
     *
     * @param string $name The name of the Queue to check for
     *
     * @return Boolean
     */
    public function has($name)
    {
        return array_key_exists($name, $this->queues);
    }

    /**
     * Returns a Single QueueProvider by Queue Name
     *
     * @param string $name
     *
     * @throws \InvalidArgumentException
     *
     * @return ProviderInterface
     */
    public function get($name)
    {
        if (!array_key_exists($name, $this->queues)) {
            throw new \InvalidArgumentException("The queue does not exist. {$name}");
        }

        return $this->queues[$name];
    }
}

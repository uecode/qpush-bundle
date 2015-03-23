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

namespace Uecode\Bundle\QPushBundle\Tests\MockClient;

use Doctrine\Common\Cache\Cache;
use Symfony\Bridge\Monolog\Logger;
use Uecode\Bundle\QPushBundle\Provider\AbstractProvider;

/**
 * @codeCoverageIgnore
 *
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class CustomMockClient extends AbstractProvider
{
    /**
     * Constructor for Provider classes
     *
     * @param string $name    Name of the Queue the provider is for
     * @param array  $options An array of configuration options for the Queue
     * @param mixed  $client  A Queue Client for the provider
     * @param Cache  $cache   An instance of Doctrine\Common\Cache\Cache
     * @param Logger $logger  An instance of Symfony\Bridge\Mongolog\Logger
     */
    public function __construct($name, array $options, $client, Cache $cache, Logger $logger)
    {
    }

    public function getProvider()
    {
    }

    public function create()
    {
    }

    public function publish(array $message, array $options = [])
    {
    }

    public function receive(array $options = [])
    {
    }

    public function delete($id)
    {
    }

    public function destroy()
    {
    }
}

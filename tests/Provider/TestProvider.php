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

namespace Uecode\Bundle\QPushBundle\Tests\Provider;

use Doctrine\Common\Cache\Cache;
use Symfony\Bridge\Monolog\Logger;

use Uecode\Bundle\QPushBundle\Provider\AbstractProvider;
use Uecode\Bundle\QPushBundle\Message\Message;

/**
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class TestProvider extends AbstractProvider
{
    /**
     * Mock Client
     *
     * @var stdClass
     */
    protected $client;

    public function __construct($name, array $options, $client, Cache $cache, Logger $logger)
    {
        $this->name     = $name;
        $this->options  = $options;
        $this->client   = $client;
        $this->cache    = $cache;
        $this->logger   = $logger;
    }

    public function getProvider()
    {
        return 'TestProvider';
    }

    /**
     * @codeCoverageIgnore
     */
    public function create() { }

    /**
     * @codeCoverageIgnore
     */
    public function publish(array $message, array $options = []) { }

    /**
     * @codeCoverageIgnore
     */
    public function receive(array $options = []) { }

    /**
     * @codeCoverageIgnore
     */
    public function delete($id) { }

    /**
     * @codeCoverageIgnore
     */
    public function destroy() { }
}

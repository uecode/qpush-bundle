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

use Doctrine\Common\Cache\Cache;
use Symfony\Bridge\Monolog\Logger;

/**
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class CustomProvider extends AbstractProvider
{
    /**
     * @type ProviderInterface
     */
    private $client;

    /**
     * @param string $name
     * @param array  $options
     * @param mixed  $client
     * @param Cache  $cache
     * @param Logger $logger
     */
    public function __construct($name, array $options, $client, Cache $cache, Logger $logger)
    {
        $this->name    = $name;
        $this->options = $options;
        $this->cache   = $cache;
        $this->logger  = $logger;

        $this->setClient($client);
    }

    /**
     * @param ProviderInterface $client
     *
     * @return CustomProvider
     */
    public function setClient(ProviderInterface $client)
    {
        $this->client = $client;

        return $this;
    }

    public function getProvider()
    {
        return 'Custom';
    }

    /**
     * Builds the configured queues
     *
     * If a Queue name is passed and configured, this method will build only that
     * Queue.
     *
     * All Create methods are idempotent, if the resource exists, the current ARN
     * will be returned
     *
     */
    public function create()
    {
        return $this->client->create();
    }

    /**
     * @return Boolean
     */
    public function destroy()
    {
        return $this->client->destroy();
    }

    /**
     * {@inheritDoc}
     *
     * This method will either use a SNS Topic to publish a queued message or
     * straight to SQS depending on the application configuration.
     *
     * @return string
     */
    public function publish(array $message, array $options = [])
    {
        return $this->client->publish($message, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function receive(array $options = [])
    {
        return $this->client->receive($options);
    }

    /**
     * {@inheritDoc}
     *
     * @return bool
     */
    public function delete($id)
    {
        return $this->client->delete($id);
    }
}

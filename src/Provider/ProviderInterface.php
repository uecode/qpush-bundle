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
interface ProviderInterface
{
    /**
     * Prefix prepended to the queue names
     */
    const QPUSH_PREFIX = 'qpush';

    /**
     * Constructor for Provider classes
     *
     * @param string $name    Name of the Queue the provider is for
     * @param array  $options An array of configuration options for the Queue
     * @param mixed  $client  A Queue Client for the provider
     * @param Cache  $cache   An instance of Doctrine\Common\Cache\Cache
     * @param Logger $logger  An instance of Symfony\Bridge\Mongolog\Logger
     */
    public function __construct($name, array $options, $client, Cache $cache, Logger $logger);

    /**
     * Returns the name of the Queue that this Provider is for
     *
     * @return string
     */
    public function getName();

    /**
     * Returns the Queue Name prefixed with the QPush Prefix
     *
     * If a Queue name is explicitly set in the configuration, use just that
     * name - which is beneficial for reuising existing queues not created by
     * qpush.  Otherwise, create the queue with the qpush prefix/
     *
     * @return string
     */
    public function getNameWithPrefix();

    /**
     * Returns the Queue Provider name
     *
     * @return string
     */
    public function getProvider();

    /**
     * Returns the Provider's Configuration Options
     *
     * @return array
     */
    public function getOptions();

    /**
     * Returns the Cache service
     *
     * @return Cache
     */
    public function getCache();

    /**
     * Returns the Logger service
     *
     * @return Logger
     */
    public function getLogger();

    /**
     * Creates the Queue
     *
     * All Create methods are idempotent, if the resource exists, the current ARN
     * will be returned
     */
    public function create();

    /**
     * Publishes a message to the Queue
     *
     * This method should return a string MessageId or Response
     *
     * @param array $message The message to queue
     * @param  array $options An array of options that override the queue defaults
     *
     * @return string
     */
    public function publish(array $message, array $options = []);

    /**
     * Polls the Queue for Messages
     *
     * Depending on the Provider, this method may keep the connection open for
     * a configurable amount of time, to allow for long polling.  In most cases,
     * this method is not meant to be used to long poll indefinitely, but should
     * return in reasonable amount of time
     *
     * @param  array $options An array of options that override the queue defaults
     *
     * @return array
     */
    public function receive(array $options = []);

    /**
     * Deletes the Queue Message
     *
     * @param mixed $id A message identifier or resource
     */
    public function delete($id);

    /**
     * Destroys a Queue and clears any Queue related Cache
     *
     * @return bool
     */
    public function destroy();

    /**
     * Logs data from the library
     *
     * This method wraps the Logger to check if logging is enabled and adds
     * the Queue name and Provider automatically to the context
     *
     * @param int    $level   The log level
     * @param string $message The message to log
     * @param array  $context The log context
     *
     * @return bool Whether the record was logged
     */
    public function log($level, $message, array $context);
}

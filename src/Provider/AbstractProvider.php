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
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Uecode\Bundle\QPushBundle\Event\MessageEvent;
use Uecode\Bundle\QPushBundle\Event\NotificationEvent;

/**
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
abstract class AbstractProvider implements ProviderInterface
{
    /**
     * QPush Queue Name
     *
     * @var string
     */
    protected $name;

    /**
     * QPush Queue Options
     *
     * @var array
     */
    protected $options;

    /**
     * Doctrine APC Cache Driver
     *
     * @var Cache
     */
    protected $cache;

    /**
     * Monolog Logger
     *
     * @var Logger
     */
    protected $logger;

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getNameWithPrefix()
    {
        if (!empty($this->options['queue_name'])) {
            return $this->options['queue_name'];
        }

        return sprintf("%s_%s", self::QPUSH_PREFIX, $this->name);
    }

    /**
     * {@inheritDoc}
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * {@inheritDoc}
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * {@inheritDoc}
     */
    public function getlogger()
    {
        return $this->logger;
    }

    /**
     * {@inheritDoc}
     */
    public function log($level, $message, array $context = [])
    {
        if (!$this->options['logging_enabled']) {
            return false;
        }

        // Add the queue name and provider to the context
        $context = array_merge(['queue' => $this->name, 'provider'  => $this->getProvider()], $context);

        return $this->logger->addRecord($level, $message, $context);
    }

    /**
     * @param NotificationEvent $event
     * @param string $eventName Name of the event
     * @param EventDispatcherInterface $dispatcher
     * @return bool
     */
    public function onNotification(NotificationEvent $event, $eventName, EventDispatcherInterface $dispatcher)
    {
        return false;
    }

    /**
     * @param MessageEvent $event
     * @return bool
     */
    public function onMessageReceived(MessageEvent $event)
    {
        return false;
    }

    /**
     * Merge override options while restricting what keys are allowed
     *
     * @param  array $options An array of options that override the queue defaults
     *
     * @return array
     */
    public function mergeOptions(array $options = [])
    {
        return array_merge($this->options, array_intersect_key($options, $this->options));
    }

    abstract public function getProvider();

    abstract public function create();

    abstract public function publish(array $message, array $options = []);

    abstract public function receive(array $options = []);

    abstract public function delete($id);

    abstract public function destroy();
}

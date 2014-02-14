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

use Uecode\Bundle\QPushBundle\Provider\ProviderInterface;

use Uecode\Bundle\QPushBundle\Event\MessageEvent;
use Uecode\Bundle\QPushBundle\Event\NotificationEvent;

/**
 * AbstractProvider
 *
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

    public function getName()
    {
        return $this->name;
    }

    public function getNameWithPrefix()
    {
        return sprintf("%s_%s", self::QPUSH_PREFIX, $this->name);
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getCache()
    {
        return $this->cache;
    }

    public function getlogger()
    {
        return $this->logger;
    }

    /**
     * @return bool
     */
    public function onNotification(NotificationEvent $event)
    {
        return false;
    }

    /**
     * @return bool
     */
    public function onMessageReceived(MessageEvent $event)
    {
        return false;
    }

    public function log($level, $message, array $context = [])
    {
        if (!$this->options['logging_enabled']) {
            return false;
        }

        // Add the queue name and provider to the context
        $context = array_merge(
            ['queue' => $this->name, 'provider'  => $this->getProvider()],
            $context
        );

        $this->logger->addRecord($level, $message, $context);
    }

    abstract public function getProvider();

    abstract public function create();

    abstract public function publish(array $message);

    abstract public function receive();

    abstract public function delete($id);

    abstract public function destroy();
}

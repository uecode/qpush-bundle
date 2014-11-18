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
use Uecode\Bundle\QPushBundle\Event\Events;
use Uecode\Bundle\QPushBundle\Event\MessageEvent;
use Uecode\Bundle\QPushBundle\Message\Message;

class SyncProvider extends AbstractProvider
{
    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    protected $dispatcher;

    public function __construct(
        $name,
        array $options,
        $client,
        Cache $cache,
        Logger $logger
    ) {
        $this->name = $name;
        $this->options = $options;
        $this->dispatcher = $client;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    public function getProvider()
    {
        return 'Sync';
    }

    public function publish(array $message, array $options = [])
    {
        $message = new Message(time(), $message, []);

        $this->dispatcher->dispatch(
            Events::Message($this->name),
            new MessageEvent($this->name, $message)
        );

        $context = ['MessageId' => $message->getId()];
        $this->log(200, 'Message received and dispatched on Sync Queue', $context);
    }

    public function create() {}

    public function destroy() {}

    public function delete($id) {}

    public function receive(array $options = []) {}
} 
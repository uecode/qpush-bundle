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

use IronMQ;
use Doctrine\Common\Cache\Cache;
use Symfony\Bridge\Monolog\Logger;
use Uecode\Bundle\QPushBundle\Event\Events;
use Uecode\Bundle\QPushBundle\Event\MessageEvent;
use Uecode\Bundle\QPushBundle\Event\NotificationEvent;
use Uecode\Bundle\QPushBundle\Message\Message;

/**
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class IronMqProvider extends AbstractProvider
{
    /**
     * IronMQ Client
     *
     * @var IronMQ
     */
    private $ironmq;

    /**
     * IronMQ Queue
     *
     * @var stdObject
     */
    private $queue;

    public function __construct($name, array $options, $client, Cache $cache, Logger $logger)
    {
        $this->name     = $name;
        $this->options  = $options;
        $this->ironmq   = $client;
        $this->cache    = $cache;
        $this->logger   = $logger;
    }

    public function getProvider()
    {
        return "IronMQ";
    }

    /**
     * {@inheritDoc}
     */
    public function create()
    {
        if ($this->options['push_notifications']) {
            $params = [
                'push_type'     => 'multicast',
                'retries'       => $this->options['notification_retries'],
                'subscribers'   => []
            ];

            foreach ($this->options['subscribers'] as $subscriber) {
                if ($subscriber['protocol'] == "email") {
                    throw new \InvalidArgumentException(
                        'IronMQ only supports `http` or `https` subscribers!'
                    );
                }

                $params['subscribers'][] = ['url' => $subscriber['endpoint']];
            }

        } else {
            $params = ['push_type' => 'pull'];
        }

        $result = $this->ironmq->updateQueue($this->getNameWithPrefix(), $params);
        $this->queue = $result;

        $key = $this->getNameWithPrefix();
        $this->cache->save($key, json_encode($this->queue));

        $this->log(200, "Queue has been created.", $params);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function destroy()
    {
        // Catch `queue not found` exceptions, throw the rest.
        try {
            $this->ironmq->deleteQueue($this->getNameWithPrefix());
        } catch ( \Exception $e) {
            if (false !== strpos($e->getMessage(), "Queue not found")) {
                $this->log(400, "Queue did not exist");
            } else {
                throw $e;
            }
        }

        $key = $this->getNameWithPrefix();
        $this->cache->delete($key);

        $this->log(200, "Queue has been destroyed.");

        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @return int
     */
    public function publish(array $message, array $options = [])
    {
        $options      = $this->mergeOptions($options);
        $publishStart = microtime(true);

        if (!$this->queueExists()) {
            $this->create();
        }

        $result = $this->ironmq->postMessage(
            $this->getNameWithPrefix(),
            json_encode([$this->name => $message]),
            [
                'timeout'       => $options['message_timeout'],
                'delay'         => $options['message_delay'],
                'expires_in'    => $options['message_expiration']
            ]
        );

        $context = [
            'message_id'    => $result->id,
            'publish_time'  => microtime(true) - $publishStart
        ];
        $this->log(200, "Message has been published.", $context);

        return $result->id;
    }

    /**
     * {@inheritDoc}
     */
    public function receive(array $options = [])
    {
        $options = $this->mergeOptions($options);

        if (!$this->queueExists()) {
            $this->create();
        }

        $messages = $this->ironmq->getMessages(
            $this->getNameWithPrefix(),
            $options['messages_to_receive'],
            $options['message_timeout']
        );

        if (!is_array($messages)) {
            $this->log(200, "No messages found in queue.");

            return [];
        }

        // Convert to Message Class
        foreach ($messages as &$message) {
            $id         = $message->id;
            $body       = $message->body;
            $metadata   = [
                'timeout'           => $message->timeout,
                'reserved_count'    => $message->reserved_count,
                'push_status'       => $message->push_status
            ];

            $message = new Message($id, $body, $metadata);

            $this->log(200, "Message has been received.", ['message_id' => $id]);
        }

        return $messages;
    }

    /**
     * {@inheritDoc}
     */
    public function delete($id)
    {
        try {
            $result = $this->ironmq->deleteMessage($this->getNameWithPrefix(), $id);
            $this->log(200, "Message deleted.", ['message_id' => $id]);
        } catch ( \Exception $e) {
            if (false !== strpos($e->getMessage(), "Queue not found")) {
                $this->log(400, "Queue did not exist");
            } else {
                throw $e;
            }
        }

        return true;
    }

    /**
     * Checks whether or not the Queue exsits
     *
     * This method relies on in-memory cache and the Cache provider
     * to reduce the need to needlessly call the create method on an existing
     * Queue.
     *
     * @return Boolean
     */
    public function queueExists()
    {
        if (isset($this->queue)) {
            return true;
        }

        $key = $this->getNameWithPrefix();
        if ($this->cache->contains($key)) {
            $this->queue = json_decode($this->cache->fetch($key));

            return true;
        }

        return false;
    }

    /**
     * Polls the Queue on Notification from IronMQ
     *
     * Dispatches the `{queue}.message_received` event
     *
     * @param NotificationEvent $event The Notification Event
     */
    public function onNotification(NotificationEvent $event)
    {
        $message = new Message(
            $event->getNotification()->getId(),
            $event->getNotification()->getBody(),
            $event->getNotification()->getMetadata()->toArray()
        );

        $this->log(
            200,
            "Message has been received from Push Notification.",
            ['message_id' => $event->getNotification()->getId()]
        );

        $messageEvent = new MessageEvent($this->name, $message);

        $event->getDispatcher()->dispatch(
            Events::Message($this->name),
            $messageEvent
        );
    }

    /**
     * Removes the message from queue after all other listeners have fired
     *
     * If an earlier listener has errored or stopped propigation, this method
     * will not fire and the Queued Message should become visible in queue again.
     *
     * Stops Event Propagation after removing the Message
     *
     * @param MessageEvent $event The SQS Message Event
     */
    public function onMessageReceived(MessageEvent $event)
    {
        $metadata = $event->getMessage()->getMetadata();

        if (!$metadata->containsKey('iron-subscriber-message-id')) {
            $id = $event->getMessage()->getId();
            $this->delete($id);
        }

        $event->stopPropagation();
    }
}

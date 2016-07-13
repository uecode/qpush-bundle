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

use IronMQ\IronMQ;
use Doctrine\Common\Cache\Cache;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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
     * @var object
     */
    private $queue;

    /**
     * @var Message[]
     */
    private $reservedMessages = [];

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
                'type' => $this->options['push_type'],
                'push'      => [
                    'rate_limit'    => $this->options['rate_limit'],
                    'retries'       => $this->options['notification_retries'],
                    'retries_delay' => $this->options['notification_retries_delay'],
                    'subscribers'   => []
                ]
            ];

            foreach ($this->options['subscribers'] as $subscriber) {
                if ($subscriber['protocol'] == "email") {
                    throw new \InvalidArgumentException(
                        'IronMQ only supports `http` or `https` subscribers!'
                    );
                }

                $params['push']['subscribers'][] = ['url' => $subscriber['endpoint']];
            }

        } else {
            $params = ['type' => 'pull'];
        }

        $queueName = $this->getNameWithPrefix();

        $this->queue = $this->ironmq->createQueue($queueName, $params);

        $this->cache->save($queueName, json_encode($this->queue));

        $this->log(200, "Queue has been created.", $params);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function destroy()
    {
        // Catch `queue not found` exceptions, throw the rest.
        $queueName = $this->getNameWithPrefix();
        try {
            $this->ironmq->deleteQueue($queueName);
            $this->queue = null;
        } catch ( \Exception $e) {
            if (false !== strpos($e->getMessage(), "Queue not found")) {
                $this->log(400, "Queue did not exist");
            } else {
                throw $e;
            }
        }

        $this->cache->delete($queueName);

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
            json_encode($message + ['_qpush_queue' => $this->name]),
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

        return (int) $result->id;
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

        $messages = $this->ironmq->reserveMessages(
            $this->getNameWithPrefix(),
            $options['messages_to_receive'],
            $options['message_timeout'],
            $options['receive_wait_time']
        );

        if (!is_array($messages)) {
            $this->log(200, "No messages found in queue.");

            return [];
        }

        // Convert to Message Class
        foreach ($messages as &$message) {
            $id         = $message->id;
            $body       = json_decode($message->body, true);
            $metadata   = [
                'reserved_count' => $message->reserved_count,
                'reservation_id' => $message->reservation_id
            ];

            unset($body['_qpush_queue']);

            $message = new Message($id, json_encode($body), $metadata);

            $this->log(200, "Message has been received.", ['message_id' => $id]);
        }

        $this->reservedMessages = array_combine(array_values(array_map(function (Message $message) {
            return $message->getId();
        }, $messages)), $messages);

        return $messages;
    }

    /**
     * {@inheritDoc}
     */
    public function delete($id)
    {
        $reservationId = $this->getReservationId($id);

        try {
            $this->ironmq->deleteMessage($this->getNameWithPrefix(), $id, $reservationId);
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
     * Checks whether or not the Queue exists
     *
     * This method relies on in-memory cache and the Cache provider
     * to reduce the need to needlessly call the create method on an existing
     * Queue.
     * @return bool
     * @throws \Exception
     */
    public function queueExists()
    {
        if (isset($this->queue)) {
            return true;
        }

        $queueName = $this->getNameWithPrefix();
        if ($this->cache->contains($queueName)) {
            $this->queue = json_decode($this->cache->fetch($queueName));

            return true;
        }
        try {
            $this->queue = $this->ironmq->getQueue($queueName);
            $this->cache->save($queueName, json_encode($this->queue));
        } catch (\Exception $e) {
            if (false !== strpos($e->getMessage(), "Queue not found")) {
                $this->log(400, "Queue did not exist");
            } else {
                throw $e;
            }
        }

        return false;
    }

    /**
     * Polls the Queue on Notification from IronMQ
     *
     * Dispatches the `{queue}.message_received` event
     *
     * @param NotificationEvent $event The Notification Event
     * @param string $eventName Name of the event
     * @param EventDispatcherInterface $dispatcher
     * @return void
     */
    public function onNotification(NotificationEvent $event, $eventName, EventDispatcherInterface $dispatcher)
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

        $dispatcher->dispatch(
            Events::Message($this->name),
            $messageEvent
        );
    }

    /**
     * Removes the message from queue after all other listeners have fired
     *
     * If an earlier listener has failed or stopped propagation, this method
     * will not fire and the Queued Message should become visible in queue again.
     *
     * Stops Event Propagation after removing the Message
     *
     * @param MessageEvent $event The SQS Message Event
     * @return void
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

    /**
     * Get queue info
     *
     * This allows to get queue size. Allowing to know if processing is finished or not
     *
     * @return mixed
     */
    public function queueInfo()
    {
        if ($this->queueExists()) {
            $queueName = $this->getNameWithPrefix();
            $this->queue = $this->ironmq->getQueue($queueName);

            return $this->queue;
        }

        return null;
    }

    /**
     * Publishes multiple message at once
     *
     * @param array $messages
     * @param array $options
     *
     * @return array
     */
    public function publishMessages(array $messages, array $options = [])
    {
        $options      = $this->mergeOptions($options);
        $publishStart = microtime(true);

        if (!$this->queueExists()) {
            $this->create();
        }

        $encodedMessages = [];
        foreach ($messages as $message) {
            $encodedMessages[] = json_encode($message + ['_qpush_queue' => $this->name]);
        }

        $result = $this->ironmq->postMessages(
            $this->getNameWithPrefix(),
            $encodedMessages,
            [
                'timeout'       => $options['message_timeout'],
                'delay'         => $options['message_delay'],
                'expires_in'    => $options['message_expiration']
            ]
        );

        $context = [
            'message_ids'    => $result->ids,
            'publish_time'  => microtime(true) - $publishStart
        ];
        $this->log(200, "Messages have been published.", $context);

        return $result->ids;
    }

    /**
     * @param $id
     * @return string|null
     */
    private function getReservationId($id)
    {
        if (!array_key_exists($id, $this->reservedMessages)) {
            return null;
        }

        $messageToDelete = $this->reservedMessages[$id];
        if (!$messageToDelete->getMetadata()->containsKey('reservation_id')) {
            return null;
        }

        return $messageToDelete->getMetadata()->get('reservation_id');
    }
}

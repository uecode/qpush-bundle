<?php

namespace Uecode\Bundle\QPushBundle\Provider;

use IronMQ;

use Doctrine\Common\Cache\Cache;

use Uecode\Bundle\QPushBundle\Provider\QueueProvider;

use Uecode\Bundle\QPushBundle\Event\Events;
use Uecode\Bundle\QPushBundle\Event\MessageEvent;
use Uecode\Bundle\QPushBundle\Event\NotificationEvent;

use Uecode\Bundle\QPushBundle\Message\Message;

class IronMqProvider extends QueueProvider
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

    public function __construct($name, array $options, Cache $cache, IronMQ $ironmq)
    {
        $this->name     = $name;
        $this->options  = $options;
        $this->cache    = $cache;
        $this->ironmq   = $ironmq;
    }


    public function getProvider()
    {
        return "IronMQ";
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
     * @return bool
     */
    public function create()
    {
        if ($this->options['push_notifications']) {
            $params = [
                'push_type'     => 'multicast',
                'retries'       => $this->options['notification_retries'],
                'subscribers'   => []
            ];

            foreach($this->options['subscribers'] as $subscriber)
            {
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

        $result = $this->ironmq->updateQueue($this->name, $params);
        $this->queue = $result;

        $key = $this->getNameWithPrefix();
        $this->cache->save($key, $this->queue);

        return true;
    }

    /**
     * @return bool
     */
    public function destroy()
    {
        $this->ironmq->deleteQueue($this->name);

        $key = $this->getNameWithPrefix();
        $this->cache->delete($key);

        return true;
    }

    /**
     * Pushes a message to the Queue
     *
     * @param array $message The message to queue
     *
     * @return int
     */
    public function publish(array $message)
    {
        if (!$this->queueExists()) {
            $this->create();
        }
    
        $result = $this->ironmq->postMessage(
            $this->name,
            json_encode([$this->name => $message]),
            [
                'timeout'       => $this->options['message_timeout'],
                'delay'         => $this->options['message_delay'],
                'expires_in'    => $this->options['message_expiration']
            ]
        );

        return $result->id;
    }

    /**
     * Polls the Queue for Messages
     *
     * @return array
     */
    public function receive()
    {
        if (!$this->queueExists()) {
            $this->create();
        }

        $messages = $this->ironmq->getMessages(
            $this->name,
            $this->options['messages_to_receive'],
            $this->options['message_timeout']
        );

        // Convert to Message Class
        foreach($messages as &$message) {
            $id         = $message->id;
            $body       = $message->body;
            $metadata   = [
                'timeout'           => $message->timeout,
                'reserved_count'    => $message->reserved_count,
                'push_status'       => $message->push_status
            ];

            $message = new Message($id, $body, $metadata);
        }

        return $messages;
    }

    /**
     * @return bool
     */
    public function delete($id)
    {
        if (!$this->queueExists()) {
            return false;
        }

        $result = $this->ironmq->deleteMessage($this->name, $message);

        return true;
    }

    /**
     * Checks whether or not the Queue exsits
     *
     * This method relies on in-memory cache and the Cache provider
     * to reduce the need to needlessly call the create method on an existing
     * Queue.
     *
     * @return string
     */
    public function queueExists()
    {
        if (isset($this->queue)) {
            return true;
        }

        $key = $this->getNameWithPrefix();
        if ($this->cache->contains($key)) {
            $this->queue = $this->cache->fetch($key);

            return true;
        }

        return false;
    }

    /**
     * Polls the Queue on Notification from IronMQ
     *
     * Dispatches the `{queue}.on_message` event
     *
     * @param NotificationEvent $event The Notification Event
     */
    public function onNotification(NotificationEvent $event)
    {
        $message = new Message(
            $event->getNotification()->getId(),
            $event->getBody(),
            $event->Metadata()->toArray()
        );

        $messageEvent = new MessageEvent($this->name, $message);
        $event->getDispatcher()->dispatch(Events::Message($this->name), $messageEvent);
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
    public function onMessage(MessageEvent $event)
    {
        $id = $event->getMessage()->getId();

        $this->delete($id);

        $event->stopPropagation();
    }
}

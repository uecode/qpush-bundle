<?php

namespace Uecode\Bundle\QPushBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Uecode\Bundle\QPushBundle\Message\Notification;

class NotificationEvent extends Event
{
    /**
     * A Subscription Notification Type
     */
    const TYPE_SUBSCRIPTION  = 'SubscriptionNotification';
    /**
     * A Message Notification Type
     */
    const TYPE_MESSAGE       = 'MessageNotification';

    /**
     * Queue name
     *
     * @var string
     */
    protected $queueName;

    /**
     * Notification Type
     *
     * @var string
     */
    protected $type;

    /**
     * Notification
     *
     * @var array
     */
    protected $notification;

    /**
     * Constructor
     *
     * @param string        $queueName      The Queue Name
     * @param string        $type           The Notification Type
     * @param Notification  $notification   The Notification
     */
    public function __construct($queueName, $type, Notification $notification)
    {
        $this->queueName    = $queueName;
        $this->type         = $type;
        $this->notification = $notification;
    }

    /**
     * Returns the Queue name
     *
     * return string
     */
    public function getQueueName()
    {
        return $this->queueName;
    }

    /**
     * Returns the Notification Type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns the Notification
     *
     * return array
     */
    public function getNotification()
    {
        return $this->notification;
    }
}

<?php

namespace Uecode\Bundle\QPushBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class NotificationEvent extends Event
{
    /**
     * SQS Queue name sent in Subject of SNS Notification
     *
     * @var string
     */
    protected $queue;

    /**
     * Entire notification from SNS
     *
     * @var array
     */
    protected $notification;

    /**
     * Constructor
     *
     * @param string    $queue          SQS Queue Name
     * @param array     $notification   SNS Notification
     */
    public function __construct($queue, $notification)
    {
        $this->queue        = $queue;
        $this->notification = $notification;
    }

    /**
     * Returns the SQS Queue name
     *
     * return string
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * Returns the entire SNS Notification
     *
     * return array
     */
    public function getNotification()
    {
        return $this->notification;
    }
}

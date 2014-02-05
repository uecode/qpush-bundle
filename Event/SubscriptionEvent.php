<?php

namespace Uecode\Bundle\QPushBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class SubscriptionEvent extends Event
{
    /**
     * Entire notification from SNS
     *
     * @var array
     */
    protected $notification;

    /**
     * Constructor
     *
     * @param string $queue        SQS Queue Name
     * @param array  $notification SNS Notification
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

    /**
     * Returns the SNS Subscription Type
     *
     * return string
     */
    public function getType()
    {
        return $this->notification['Type'];
    }

    /**
     * Returns the SNS Topic ARN
     *
     * return string
     */
    public function getTopicArn()
    {
        return $this->notification['TopicArn'];
    }

    /**
     * Returns the SNS Subscription Url
     *
     * return string
     */
    public function getSubscriptionUrl()
    {
        if ($this->getSubscriptionEventType() == "SubscriptionConfirmation") {
            return $this->notification['SubscribeURL'];
        }

        return $this->notification['UnsubscribeURL'];
    }

    /**
     * Returns the SNS Subscription Token
     *
     * return string
     */
    public function getToken()
    {
        return $this->notification['Token'];
    }

}

<?php

namespace Uecode\Bundle\QPushBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Uecode\Bundle\QpushBundle\Message\Message;

class MessageEvent extends Event
{
    /**
     * Queue name
     *
     * @var string
     */
    protected $queueName;

    /**
     * Message
     *
     * @var mixed
     */
    protected $message;

    /**
     * Constructor.
     *
     * @param string    $queue      The queue name
     * @param Message   $message    The Message
     */
    public function __construct($queueName, Message $message)
    {
        $this->queueName    = $queueName;
        $this->message      = $message;
    }

    /**
     * Return the SQS Queue Name
     *
     * @return string
     */
    public function getQueueName()
    {
        return $this->queueName;
    }

    /**
     * Return the Full SQS Message
     *
     * @return Message
     */
    public function getMessage()
    {
        return $this->message;
    }
}

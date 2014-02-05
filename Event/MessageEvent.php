<?php

namespace Uecode\Bundle\QPushBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class MessageEvent extends Event
{
    /**
     * SQS Queue name
     *
     * @var string
     */
    protected $queue;

    /**
     * SQS Message
     *
     * @var array
     */
    protected $message;

    /**
     * Constructor.
     *
     * @param string    $queue          The SQS Queue Name
     * @param array     $message        SQS Message
     * @param string    $receiptHanle   The SQS Message Receipt Handle
     */
    public function __construct($queue, $message)
    {
        $this->queue            = $queue;
        $this->message          = $message;
    }

    /**
     * Return the SQS Queue Name
     *
     * @return string
     */
    public function getQueueName()
    {
        return $this->queue;
    }

    /**
     * Return the Full SQS Message
     *
     * @return array
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Return the SQS Message Body
     *
     * This method assumes the the body is a json string and will 
     * `json_decode` the Message Body before returning
     *
     * @return array
     */
    public function getMessageBody()
    {
        return json_decode($this->message['Body'], true);
    }

    /**
     * Return the SQS Message Receipt Handle
     *
     * @return string
     */
    public function getReceiptHandle()
    {
        return $this->message['ReceiptHandle'];
    }
}

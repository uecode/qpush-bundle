<?php

namespace Uecode\Bundle\QPushBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class MessageEvent extends Event
{
    /**
     * Queue name
     *
     * @var string
     */
    protected $queue;

    /**
     * Message
     *
     * @var array
     */
    protected $message;

    /**
     * Message Meta Data
     *
     * @var array
     */
    protected $metadata;

    /**
     * Constructor.
     *
     * @param string $queue     The Queue Name
     * @param array  $message   Message
     * @param string $metadata  Optional Message Metadata
     */
    public function __construct($queue, array $message, array $metadata = array())
    {
        $this->queue    = $queue;
        $this->message  = $message;
        $this->metadata = $metadata;
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
     * Return the SQS Message Receipt Handle
     *
     * @return string
     */
    public function getMetadata()
    {
        return $this->metadata;
    }
}

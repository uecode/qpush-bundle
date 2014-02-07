<?php

namespace Uecode\Bundle\QPushBundle\Queue;

use Uecode\Bundle\QPushBundle\Queue\QueueProviderInterface;

class QPushQueueRegistry
{
    /**
     * All services tagged with `uecode_qpush.receive`
     * @var array
     */
    private $queues;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->queues = [];
    }

    /**
     * Adds a Listener to the chain based on priority
     *
     * @param string                    $name    The name of the Queue
     * @param QueueProviderInterface    $service The QueueProvider
     */
    public function addQueue($name, QueueProviderInterface $service)
    {
        $this->queues[$name] = $service;
    }

    /**
     * Returns the Queues
     *
     * @return array|QPushClientService[]
     */
    public function all()
    {
        return $this->queues;
    }

    /**
     * Checks whether a Queue exists in the Regisitry
     *
     * @param string $name The name of the Queue to check for
     *
     * @return boolean
     */
    public function has($name)
    {
        return array_key_exists($name, $this->queues);
    }

    /**
     * Returns a Single QPushClientService by Queue Name
     *
     * @return QPushClientService
     */
    public function get($name)
    {
        if (!array_key_exists($name, $this->queues)) {
            throw new \InvalidArgumentException("The queue does not exist. {$name}");
        }

        return $this->queues[$name];
    }

}

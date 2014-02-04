<?php

namespace Uecode\Bundle\QPushBundle\DependencyInjection\Compiler;

use Uecode\Bundle\QPushBundle\Service\QPushClientService;

use InvalidArgumentException;

class QPushRegistry
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
     * @param string                $name       The name of the Queue
     * @param QPushClientService    $service    The QPushClientService
     */
    public function addQueue($name, QPushClientService $service)
    {
        $this->queues[$name] = $service;
    }

    /**
     * Returns the Queues
     *
     * @return array|QPushClientService[]
     */
    public function getQueues()
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
    public function hasQueue($name)
    {
        return array_key_exists($name, $this->queues);
    }

    /**
     * Returns a Single QPushClientService by Queue Name
     *
     * @return QPushClientService
     */
    public function getQueue($name)
    {
        if (!array_key_exists($name, $this->queues)) {
            throw new InvalidArgumentException("The queue does not exist. {$name}");
        }

        return $this->queues[$name];
    }

}

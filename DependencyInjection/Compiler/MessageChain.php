<?php

namespace Uecode\Bundle\QPushBundle\DependencyInjection\Compiler;

use Uecode\Bundle\QPushBundle\EventListener\MessageListener;

use SplPriorityQueue;
use RuntimeException;

class MessageChain
{
    /**
     * All services tagged with `uecode_qpush.receive`
     * @var array
     */
    protected $listeners;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->listeners = new SplPriorityQueue();
    }

    /**
     * Adds a Listener to the chain based on priority
     *
     * @param MessageListener   $listener   A service tagged with `uecode_qpush.receive'
     * @param string            $queue      The queue to listen on
     * @param int               $priority   Order of priority for services
     */
    public function addMessageListener(MessageListener $listener, $queue, $priority = 0)
    {
        if (!is_numeric($priority) && $priority > -1) {
            throw new \RuntimeException(
                "Service tag `priority` must be an integer between 0 and 255");
        }
        $this->listeners->insert(['queue' => $queue, 'service' => $listener], $priority);
    }

    /**
     * Converts and returns the SplPriorityQueue of Listeners as an array
     *
     * @return array
     */
    public function getListeners()
    {
        return iterator_to_array($this->listeners);
    }

}

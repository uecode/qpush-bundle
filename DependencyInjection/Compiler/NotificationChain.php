<?php

namespace Uecode\Bundle\QPushBundle\DependencyInjection\Compiler;

use Uecode\Bundle\QPushBundle\EventListener\NotificationListener;

use SplPriorityQueue;
use RuntimeException;

class NotificationChain
{
    /**
     * All services tagged with `uecode_qpush.notify`
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
     * @param NotificationListener  $listener   A service tagged with `uecode_qpush.notify`
     * @param int                   $priority   Order of priority for services
     */
    public function addNotificationListener(NotificationListener $listener, $priority = 0)
    {
        if (!is_numeric($priority) && $priority > -1) {
            throw new \RuntimeException(
                "Service tag `priority` must be an integer between 0 and 255");
        }
        $this->listeners->insert(['service' => $listener], $priority);
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

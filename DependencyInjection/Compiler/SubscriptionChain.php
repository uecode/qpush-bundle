<?php

namespace Uecode\Bundle\QPushBundle\DependencyInjection\Compiler;

use Uecode\Bundle\QPushBundle\EventListener\SubscriptionListener;

use SplPriorityQueue;
use RuntimeException;

class SubscriptionChain
{
    /**
     * All services tagged with `uecode_qpush.subscription`
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
     * @param SubscriptionListener  $listener   A service tagged with `uecode_qpush.subscription`
     * @param string                $event      The Subscription event to fire on
     * @param int                   $priority   Order of priority for services
     */
    public function addSubscriptionListener(SubscriptionListener $listener, $event, $priority = 0)
    {
        if (!is_numeric($priority) && $priority > -1) {
            throw new \RuntimeException(
                "Service tag `priority` must be an integer between 0 and 255");
        }
        if (!is_string($event) || !in_array($event, [ 'subscription', 'unsubscribe' ])) {
            throw new \RuntimeException(
                "Service tag `event` must be either 'subscription' or 'unsubcribe'");
        }
        $this->listeners->insert(['event' => $event, 'service' => $listener], $priority);
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

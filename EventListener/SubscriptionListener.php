<?php

namespace Uecode\Bundle\QPushBundle\EventListener;

use Uecode\Bundle\QpushBundle\Event\SubscriptionEvent;

interface SubscriptionListener
{
    /**
     * Handles a SubscriptionEvent for the Queue
     *
     * Some Queue Providers (like AWS SNS) send and require confirmation 
     * requests to add or remove subscriptions to the Queue
     *
     * @param SubscriptionEvent $event The Subscription Event
     */
    public function onSubscription(SubscriptionEvent $event);
}

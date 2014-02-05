<?php

namespace Uecode\Bundle\QPushBundle\EventListener;

use Uecode\Bundle\QpushBundle\Event\SubscriptionEvent;

interface SubscriptionListener
{
    /**
     * Method for custom event handlers to process SNS Subscription Event
     *
     * The SubscriptionEvent can be either for a `SubscriptionConfirmation` or
     * a `UnsubscribeConfirmation`.  The method should check the event type.
     *
     * @param   SubscriptionEvent $event SNS Subscription Event
     */
    public function onSubscription(SubscriptionEvent $event);
}

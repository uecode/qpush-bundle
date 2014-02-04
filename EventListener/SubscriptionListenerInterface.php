<?php

namespace Uecode\Bundle\QPushBundle\Service;

use Uecode\Bundle\QpushBundle\Event\SubscriptionEvent;

interface SubscriptionListener
{
    /**
     * Method for custom event handlers to process SNS Subscription Event
     *
     * Methods should return a Boolean value. Returning False will stop event 
     * propagation. 
     *
     * @param   SubscriptionEvent $event SNS Subscription Event
     * @return  boolean
     */
    public function onSubscriptionChange(SubscriptionEvent $event);
}

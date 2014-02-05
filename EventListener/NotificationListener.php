<?php

namespace Uecode\Bundle\QPushBundle\EventListener;

use Uecode\Bundle\QpushBundle\Event\NotificationEvent;

interface NotificationListener
{
    /**
     * Method for custom event handlers to process SNS Notification Event
     *
     * @param SubscriptionEvent $event SNS Subscription Event
     */
    public function onNotify(NotificationEvent $event);
}

<?php

namespace Uecode\Bundle\QPushBundle\Service;

use Uecode\Bundle\QpushBundle\Event\NotificationEvent;

interface NotificationListener
{
    /**
     * Method for custom event handlers to process SNS Notification Event
     *
     * @param   SubscriptionEvent $event SNS Subscription Event
     * @return  boolean
     */
    public function onNotificationReceived(NotificationEvent $event);
}

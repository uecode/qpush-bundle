<?php

namespace Uecode\Bundle\QPushBundle\EventListener;

use Uecode\Bundle\QpushBundle\Event\NotificationEvent;

interface NotificationListenerInterface
{
    /**
     * Handles a NotificaitonEvent from the Queue
     *
     * The NotificaitonEvent is used to notify subscribers that a Message is
     * waiting in the Queue 
     *
     * @param NotificationEvent $event The SNS Notification Event
     */
    public function onNotify(NotificationEvent $event);
}

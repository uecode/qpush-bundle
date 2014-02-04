<?php

namespace Uecode\Bundle\QpushBundle\Controller;

use Symfony\Component\HttpFoundation\Response;

use Uecode\Bundle\QPushBundle\Event\Events;
use Uecode\Bundle\QPushBundle\Event\SnsnotificationEvent;

/**
 * SNS notification Controller
 *
 * SNS Notifications are directed to the correct action through use of a 
 * Request Listener looking for custom SNS headers.
 */
class QpushSnsController
{
    /**
     * Dispatches SNS notification Event to services to poll SQS Queue
     *
     * @param string    $queue          SQS Queue Name
     * @param array     $notification   SNS notification
     */
    protected function notifyAction($queue, $notification)
    {
        $dispatcher = $this->get('event_dispatcher');
        $dispatcher->dispatch(Events::NOTIFY, new SnsNotificationEvent($queue, $notification));

        return new Response('success', 200);
    }

    /**
     * Dispatches SNS Subscription Event to services to confirm Subsciption change
     *
     * @param string    $queue          SQS Queue Name
     * @param array     $notification   SNS Notification
     */
    protected function subscriptionAction($notification)
    {
        $dispatcher = $this->get('event_dispatcher');
        $dispatcher->dispatch(Events::SUBSCRIPTION, new SnsSubscriptionEvent($notification));

        return new Response('success', 200);
    }
}

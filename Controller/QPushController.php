<?php

namespace Uecode\Bundle\QpushBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use Uecode\Bundle\QPushBundle\Event\Events;
use Uecode\Bundle\QPushBundle\Event\NotificationEvent;
use Uecode\Bundle\QPushBundle\Event\SubscriptionEvent;

/**
 * QPush Controller
 *
 * SNS Notifications are directed to the correct action through use of a
 * Request Listener looking for custom SNS headers.
 */
class QPushController extends Controller
{
    /**
     * Dispatches SNS notification Event to services to poll SQS Queue
     *
     * @param string $queue        SQS Queue Name
     * @param array  $notification SNS notification
     */
    public function notifyAction($queue, $notification)
    {
        error_log('dispatched notification event, automagically');
        $dispatcher = $this->get('event_dispatcher');
        $dispatcher->dispatch(Events::NOTIFY, new NotificationEvent($queue, $notification));

        return new Response('success', 200);
    }

    /**
     * Dispatches SNS Subscription Event to services to confirm Subsciption change
     *
     * @param string $queue        SQS Queue Name
     * @param array  $notification SNS Notification
     */
    public function subscriptionAction($notification)
    {
        error_log('dispatched subscription event, automagically');
        $dispatcher = $this->get('event_dispatcher');
        $dispatcher->dispatch(Events::SUBSCRIPTION, new SubscriptionEvent($notification));

        return new Response('success', 200);
    }
}

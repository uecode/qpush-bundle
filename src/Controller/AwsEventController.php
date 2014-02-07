<?php

namespace Uecode\Bundle\QpushBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use Uecode\Bundle\QPushBundle\Event\Events;
use Uecode\Bundle\QPushBundle\Event\NotificationEvent;
use Uecode\Bundle\QPushBundle\Event\SubscriptionEvent;

/**
 * AWS Event Controller
 *
 * SNS Notifications are directed to the correct action through use of a
 * Request Listener looking for custom SNS headers.
 */
class AwsEventController extends Controller
{
    /**
     * Dispatches SNS notification Event to services to poll SQS Queue
     *
     * @param string $queue        SQS Queue Name
     * @param array  $notification SNS notification
     *
     * @return Response
     */
    public function notifyAction($queue, $notification)
    {
        $dispatcher = $this->get('event_dispatcher');
        $dispatcher->dispatch(Events::Notify($queue), new NotificationEvent($queue, $notification));

        return new Response('success', 200);
    }

    /**
     * Dispatches SNS Subscription Event to services to confirm Subsciption change
     *
     * @param string $queue        SQS Queue Name
     * @param array  $notification SNS Notification
     *
     * @return Response
     */
    public function subscriptionAction($queue, $notification)
    {
        $dispatcher = $this->get('event_dispatcher');
        $dispatcher->dispatch(Events::Subscription($queue), new SubscriptionEvent($queue, $notification));

        return new Response('success', 200);
    }
}

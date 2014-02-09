<?php

namespace Uecode\Bundle\QPushBundle\EventListener;

use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Uecode\Bundle\QPushBundle\Message\Message;
use Uecode\Bundle\QPushBundle\Message\Notification;
use Uecode\Bundle\QPushBundle\Event\MessageEvent;
use Uecode\Bundle\QPushBundle\Event\NotificationEvent;

class RequestListener
{
    /**
     * Symfony Event Dispatcher
     *
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * Constructor.
     *
     * @param EventDispatcherInterface $dispatcher A Symfony Event Dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Kernel Request Event Handler for QPush Notifications
     *
     * @param GetResponseEvent $event The Kernel Request's GetResponseEvent
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (HttpKernel::MASTER_REQUEST != $event->getRequestType()) {
            return;
        }

        if ($event->getRequest()->headers->has('x-amz-sns-message-type')) {
             $this->handleSnsNotifications($event);

            $event->setResponse(new Response("", 200));
        }

        if ($event->getRequest()->headers->has('iron-message-id')) {
            $this->handleIronMqNotifications($event);

            $event->setResponse(new Response("", 200));
        }
    }

    /**
     * Handles Messages sent from a IronMQ Push Queue
     *
     * @param GetResponseEvent $event The Kernel Request's GetResponseEvent
     */
    private function handleIronMqNotifications(GetResponseEvent $event)
    {
        $headers    = $event->getRequest()->headers;
        $messageId  = $headers->get('iron-message-id');

        // We add the message in an array with Queue as the property name
        $message    = json_decode($event->getRequest()->getContent(), true);
        $queue      = key($message);
        $metadata   = [
            'iron-subscriber-message-id'    => $headers->get('iron-subscriber-message-id'),
            'iron-subscriber-message-url'   => $headers->get('iron-subscriber-message-url')
        ];
        
        $notification = new Notificiation(
            $messageId,
            NotificationEvent::TYPE_MESSAGE,
            $message[$queue],
            $metadata
        );

        $dispatcher->dispatch(
            Events::Notification($queue),
            new NotificationEvent($queue, $notification)
        );

    }

    /**
     * Handles Notifications sent from AWS SNS
     *
     * @param GetResponseEvent $event The Kernel Request's GetResponseEvent
     */
    private function handleSnsNotifications(GetResponseEvent $event)
    {
        $notification = json_decode($event->getRequest()->getContent(), true);

        if (false === strpos($notification['TopicArn'], 'uecode_qpush_')) {
            return;
        }

        $type = $event->getRequest()->headers->get('x-amz-sns-message-type');

        $metadata = [
            'Type'      => $notification['Type'],
            'Subject'   => $notification['Subject'],
            'TopicArn'  => $notification['TopicArn'],
            'Timestamp' => $notification['Timestamp'],
        ];

        if ($type === 'Notification') {

            // We put the queue name in the Subject field
            $queue          = $notification['Subject'];

            $notification   = new Notification(
                $notification['MessageId'],
                NotificationEvent::TYPE_MESSAGE,
                $notification['Message'],
                $metadata
            );

        } else {
            // For subscription notifications, we need to parse the Queue from 
            // the Topic ARN
            $arnParts           = explode(':', $notification['TopicArn']);
            $last               = end($arnParts);
            $queue              = str_replace('uecode_qpush_', '', $last);

            // Get the token for the Subscription Confirmation
            $metadata['Token']  = $notification['Token'];

            $notification = new Notification(
                $notification['MessageId'],
                NotificationEvent::TYPE_SUBSCRIPTION,
                $notification['Message'],
                $metadata
            );
        }

        $dispatcher->dispatch(
            Events::Notification($queue), 
            new NotificationEvent($queue, $notification)
        );

    }
}

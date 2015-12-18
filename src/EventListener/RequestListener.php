<?php

/**
 * Copyright 2014 Underground Elephant
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @package     qpush-bundle
 * @copyright   Underground Elephant 2014
 * @license     Apache License, Version 2.0
 */

namespace Uecode\Bundle\QPushBundle\EventListener;

use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Uecode\Bundle\QPushBundle\Message\Notification;
use Uecode\Bundle\QPushBundle\Event\Events;
use Uecode\Bundle\QPushBundle\Event\NotificationEvent;

/**
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
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
        $this->dispatcher   = $dispatcher;
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
            $result = $this->handleSnsNotifications($event);
            $event->setResponse(new Response($result, 200));
        }

        if ($event->getRequest()->headers->has('iron-message-id')) {
            $result = $this->handleIronMqNotifications($event);
            $event->setResponse(new Response($result, 200));
        }
    }

    /**
     * Handles Messages sent from a IronMQ Push Queue
     *
     * @param GetResponseEvent $event The Kernel Request's GetResponseEvent
     * @return string|void
     */
    private function handleIronMqNotifications(GetResponseEvent $event)
    {
        $headers    = $event->getRequest()->headers;
        $messageId  = $headers->get('iron-message-id');

        // We add the message in an array with Queue as the property name
        $message    = json_decode($event->getRequest()->getContent(), true);

        if (empty($message['_qpush_queue'])) {
            return;
        }

        $queue      = $message['_qpush_queue'];
        $metadata   = [
            'iron-subscriber-message-id'  => $headers->get('iron-subscriber-message-id'),
            'iron-subscriber-message-url' => $headers->get('iron-subscriber-message-url')
        ];

        unset($message['_qpush_queue']);

        $notification = new Notification(
            $messageId,
            $message,
            $metadata
        );

        $this->dispatcher->dispatch(
            Events::Notification($queue),
            new NotificationEvent($queue, NotificationEvent::TYPE_MESSAGE, $notification)
        );

        return "IronMQ Notification Received.";
    }

    /**
     * Handles Notifications sent from AWS SNS
     *
     * @param GetResponseEvent $event The Kernel Request's GetResponseEvent
     * @return string
     */
    private function handleSnsNotifications(GetResponseEvent $event)
    {
        $notification = json_decode((string)$event->getRequest()->getContent(), true);

        $type = $event->getRequest()->headers->get('x-amz-sns-message-type');

        $metadata = [
            'Type'      => $notification['Type'],
            'TopicArn'  => $notification['TopicArn'],
            'Timestamp' => $notification['Timestamp'],
        ];

        if ($type === 'Notification') {

            // We put the queue name in the Subject field
            $queue                  = $notification['Subject'];
            $metadata['Subject']    = $queue;

            $notification           = new Notification(
                $notification['MessageId'],
                $notification['Message'],
                $metadata
            );

            $this->dispatcher->dispatch(
                Events::Notification($queue),
                new NotificationEvent($queue, NotificationEvent::TYPE_MESSAGE, $notification)
            );

            return "SNS Message Notification Received.";
        }

        // For subscription notifications, we need to parse the Queue from
        // the Topic ARN
        $arnParts           = explode(':', $notification['TopicArn']);
        $last               = end($arnParts);
        $queue              = str_replace('qpush_', '', $last);

        // Get the token for the Subscription Confirmation
        $metadata['Token']  = $notification['Token'];

        $notification = new Notification(
            $notification['MessageId'],
            $notification['Message'],
            $metadata
        );

        $this->dispatcher->dispatch(
            Events::Notification($queue),
            new NotificationEvent($queue, NotificationEvent::TYPE_SUBSCRIPTION, $notification)
        );

        return "SNS Subscription Confirmation Received.";
    }
}

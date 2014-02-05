<?php

namespace Uecode\Bundle\QPushBundle\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Uecode\Bundle\QPushBundle\Event\Events;
use Uecode\Bundle\QPushBundle\Event\MessageEvent;
use Uecode\Bundle\QPushBundle\Event\NotificationEvent;
use Uecode\Bundle\QPushBundle\Event\SubscriptionEvent;
use Uecode\Bundle\QPushBundle\DependencyInjection\Comiler\MessageChain;
use Uecode\Bundle\QPushBundle\DependencyInjection\Comiler\NotificationChain;
use Uecode\Bundle\QPushBundle\DependencyInjection\Comiler\SubscriptionChain;

class QPushEventSubscriber implements EventSubscriberInterface
{
    /**
     * Arraservices tagged with `uecode_qpush.receive` event
     * @var
     */
    protected $messageChain;

    /**
     * All services tagged with `uecode_qpush.notify` event
     * @var array
     */
    protected $notificationChain;

    /**
     * All services tagged with `uecode_qpush.subscription` event
     * @var array
     */
    protected $subscriptionChain;

    /**
     * Constructor.
     *
     * @param NotificationChain $notificationChain Services tagged for notifications
     * @param SubscriptionChain $subscriptionChain Services tagged for subscriptions
     * @param MessageChain      $messageChain      Services tagged for messages
     */
    public function __construct(NotificationChain $notificationChain,
        SubscriptionChain $subscriptionChain, MessageChain $messageChain)
    {
        $this->notificationChain    = $notificationChain;
        $this->subscriptionChain    = $subscriptionChain;
        $this->messageChain         = $messageChain;
    }

    public static function getSubscribedEvents()
    {
        return [
            Events::NOTIFY          => ['onNotify', 0],
            EVENTS::SUBSCRIPTION    => ['onSubscription', 0],
            Events::RECEIVE         => ['onReceive', 0]
        ];
    }

    public function onNotify(NotificationEvent $event)
    {
        $listeners = $this->notificationChain->getListeners();
        foreach ($listeners as $listener) {
            $listener['service']->process($event);
        }
    }

    public function onSubscription(SubscriptionEvent $event)
    {
        $listeners = $this->notificationChain->getListeners();

        $type = str_replace('uecode_qpush.', '', $event->getName());
        foreach ($listeners as $listener) {
            if ($listener['event'] !== $type) {
                $listener['service']->process($event);
            }
        }
    }

    public function onReceive(MessageEvent $event)
    {
        $listeners = $this->notificationChain->getListeners();

        $queue = $event->getQueue();
        foreach ($listeners as $listener) {
            if ($listener['queue'] !== $queue) {
                $listener['service']->process($event);
            }
        }
    }
}

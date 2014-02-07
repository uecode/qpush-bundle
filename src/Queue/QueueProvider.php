<?php

namespace Uecode\Bundle\QPushBundle\Queue;

use Doctrine\Common\Cache\Cache;

use Uecode\Bundle\QPushBundle\Queue\QueueProviderInterface;

use Uecode\Bundle\QPushBundle\Event\MessageEvent;
use Uecode\Bundle\QPushBundle\Event\NotificationEvent;
use Uecode\Bundle\QPushBundle\Event\SubscriptionEvent;

abstract class QueueProvider implements QueueProviderInterface
{
    /**
     * QPush Queue Name
     *
     * @var string
     */
    protected $name;

    /**
     * QPush Queue Options
     *
     * @var array
     */
    protected $options;

    /**
     * Doctrine APC Cache Driver
     *
     * @var Cache
     */
    protected $cache;

    final public function __construct($name, array $options, Cache $cache)
    {
        $this->name     = $name;
        $this->options  = $options;
        $this->cache    = $cache;
    }

    /**
     * Allows for a Service to be injected into the Queue Provider
     *
     * Override this method when using a Service with the Queue
     * Provider
     *
     * @return bool
     */
    public function setService($service)
    {
        return false;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getNameWithPrefix()
    {
        return sprintf("%s_%s", self::QPUSH_PREFIX, $this->name);
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getCache()
    {
        return $this->cache;
    }

    public function createMessageEvent($message)
    {
        return new MessageEvent($this->name, $message);
    }

    /**
     * @return bool
     */
    public function onSubscription(SubscriptionEvent $event)
    {
        return false;
    }

    /**
     * @return bool
     */
    public function onNotify(NotificationEvent $event)
    {
        return false;
    }

    /**
     * @return bool
     */
    public function onMessage(MessageEvent $event)
    {
        return false;
    }

    abstract public function getProvider();

    abstract public function create();

    abstract public function publish(array $message);

    abstract public function receive();

    abstract public function delete($message);
}

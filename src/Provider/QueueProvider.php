<?php

namespace Uecode\Bundle\QPushBundle\Provider;

use Doctrine\Common\Cache\Cache;

use Uecode\Bundle\QPushBundle\Queue\QueueProviderInterface;

use Uecode\Bundle\QPushBundle\Event\MessageEvent;
use Uecode\Bundle\QPushBundle\Event\NotificationEvent;

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

    abstract public function delete($id);

    abstract public function destroy();
}

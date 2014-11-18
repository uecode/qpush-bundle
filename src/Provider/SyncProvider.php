<?php

namespace Uecode\Bundle\QPushBundle\Provider;


use Doctrine\Common\Cache\Cache;
use Symfony\Bridge\Monolog\Logger;
use Uecode\Bundle\QPushBundle\Event\Events;
use Uecode\Bundle\QPushBundle\Event\MessageEvent;
use Uecode\Bundle\QPushBundle\Message\Message;

class SyncProvider extends AbstractProvider
{
    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    protected $dispatcher;

    public function __construct(
        $name,
        array $options,
        $client,
        Cache $cache,
        Logger $logger
    ) {
        $this->name = $name;
        $this->options = $options;
        $this->dispatcher = $client;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    public function getProvider()
    {
        return 'Sync';
    }

    public function publish(array $message, array $options = [])
    {
        $message = new Message(time(), $message, []);

        $this->dispatcher->dispatch(
            Events::Message($this->name),
            new MessageEvent($this->name, $message)
        );

        $context = ['MessageId' => $message->getId()];
        $this->log(200, 'Message received and dispatched on Sync Queue', $context);
    }

    public function create() {}

    public function destroy() {}

    public function delete($id) {}

    public function receive(array $options = []) {}
} 
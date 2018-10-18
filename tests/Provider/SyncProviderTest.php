<?php

namespace Uecode\Bundle\QPushBundle\Tests\Provider;

use Uecode\Bundle\QPushBundle\Event\Events;
use Uecode\Bundle\QPushBundle\Provider\SyncProvider;
use PHPUnit\Framework\TestCase;

class SyncProviderTest extends TestCase
{
    /**
     * @var \Uecode\Bundle\QPushBundle\Provider\SyncProvider
     */
    protected $provider;

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var \Monolog\Logger
     */
    protected $logger;

    public function setUp()
    {
        $this->dispatcher = $this->createMock(
            'Symfony\Component\EventDispatcher\EventDispatcherInterface'
        );

        $this->provider = $this->getSyncProvider();
    }

    public function testGetProvider()
    {
        $provider = $this->provider->getProvider();

        $this->assertEquals('Sync', $provider);
    }

    public function testPublish()
    {
        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                Events::Message($this->provider->getName()),
                new \PHPUnit_Framework_Constraint_IsInstanceOf('Uecode\Bundle\QPushBundle\Event\MessageEvent')
            );

        $this->provider->publish(['foo' => 'bar']);
    }

    public function testCreate()
    {
        $this->setNoOpExpectation();

        $this->provider->create();
    }

    public function testDestroy()
    {
        $this->setNoOpExpectation();

        $this->provider->destroy();
    }

    public function testDelete()
    {
        $this->setNoOpExpectation();

        $this->provider->delete('foo');
    }

    public function testReceive()
    {
        $this->setNoOpExpectation();

        $this->provider->receive();
    }


    protected function getSyncProvider()
    {
        $options = [
            'logging_enabled'       => false,
            'push_notifications'    => true,
            'notification_retries'  => 3,
            'message_delay'         => 0,
            'message_timeout'       => 30,
            'message_expiration'    => 604800,
            'messages_to_receive'   => 1,
            'receive_wait_time'     => 3,
            'subscribers'           => [
                [ 'protocol' => 'http', 'endpoint' => 'http://fake.com' ]
            ]
        ];

        $cache = $this->createMock(
            'Doctrine\Common\Cache\PhpFileCache',
            [],
            ['/tmp', 'qpush.aws.test.php']
        );

        $this->logger = $this->createMock(
            'Monolog\Logger', [], ['qpush.test']
        );

        return new SyncProvider('test', $options, $this->dispatcher, $cache, $this->logger);
    }

    protected function setNoOpExpectation()
    {
        $this->dispatcher
            ->expects($this->never())
            ->method(new \PHPUnit_Framework_Constraint_IsAnything());

        $this->logger
            ->expects($this->never())
            ->method(new \PHPUnit_Framework_Constraint_IsAnything());
    }
}

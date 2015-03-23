<?php

namespace Uecode\Bundle\QPushBundle\Tests\Provider;


use Uecode\Bundle\QPushBundle\Provider\CustomProvider;
use Uecode\Bundle\QPushBundle\Tests\MockClient\CustomMockClient;

class CustomProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Uecode\Bundle\QPushBundle\Provider\SyncProvider
     */
    private $provider;

    /**
     * @var \Symfony\Bridge\Monolog\Logger
     */
    private $logger;

    private $mock;

    public function setUp()
    {
        $this->provider = $this->getCustomProvider();
    }

    public function testGetProvider()
    {
        $provider = $this->provider->getProvider();

        $this->assertEquals('Custom', $provider);
    }

    public function testPublish()
    {
        $this->setNoOpExpectation();

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


    protected function getCustomProvider()
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
            'subscribers'           => []
        ];

        $cache = $this->getMock(
            'Doctrine\Common\Cache\PhpFileCache',
            [],
            ['/tmp', 'qpush.custom.test.php']
        );

        $this->logger = $this->getMock(
            'Symfony\Bridge\Monolog\Logger', [], ['qpush.test']
        );

        $this->mock = new CustomMockClient('custom', $options, null, $cache, $this->logger);

        return new CustomProvider('test', $options, $this->mock, $cache, $this->logger);
    }

    protected function setNoOpExpectation()
    {
        $this->logger
            ->expects($this->never())
            ->method(new \PHPUnit_Framework_Constraint_IsAnything());
    }
} 
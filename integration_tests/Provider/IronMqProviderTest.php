<?php

namespace Uecode\Bundle\QPushBundle\IntegrationTests\Provider;

use IronMQ\IronMQ;
use Uecode\Bundle\QPushBundle\Event\MessageEvent;
use Uecode\Bundle\QPushBundle\Event\NotificationEvent;
use Uecode\Bundle\QPushBundle\Message\Notification;
use Uecode\Bundle\QPushBundle\Provider\IronMqProvider;

class IronMqProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Mock Client
     *
     * @var IronMqProvider
     */
    protected $provider;
    private $project_id;

    /**
     * @var IronMQ
     */
    private $client;

    public function setUp()
    {
        if (!defined("IRONMQ_TOKEN") || IRONMQ_TOKEN == 'CHANGE_ME') {
            throw new \RuntimeException('"IRONMQ_TOKEN" must be defined in tests/bootstrap.php');
        }
        if (!defined("IRONMQ_PROJECT_ID") || IRONMQ_PROJECT_ID == 'CHANGE_ME') {
            throw new \RuntimeException('"IRONMQ_PROJECT_ID" must be defined in tests/bootstrap.php');
        }
        if (!defined("IRONMQ_HOST")) {
            throw new \RuntimeException('"IRONMQ_HOST" must be defined in tests/bootstrap.php');
        }
        $this->client = new IronMQ([
            'token'         => IRONMQ_TOKEN,
            'project_id'    => IRONMQ_PROJECT_ID,
            'host'          => IRONMQ_HOST
        ]);

        $this->provider = $this->getIronMqProvider();
    }

    public function tearDown()
    {
        if (!is_null($this->provider)) {
            $this->provider->destroy();
            $this->provider = null;
        }
    }

    private function getIronMqProvider(array $options = [])
    {
        $options = array_merge(
            [
                'logging_enabled'            => false,
                'push_notifications'         => true,
                'push_type'                  => 'multicast',
                'notification_retries'       => 3,
                'notification_retries_delay' => 60,
                'message_delay'              => 0,
                'message_timeout'            => 30,
                'message_expiration'         => 604800,
                'messages_to_receive'        => 1,
                'rate_limit'                 => -1,
                'receive_wait_time'          => 3,
                'subscribers'                => [
                    [ 'protocol' => 'http', 'endpoint' => 'http://fake.com' ]
                ]
            ],
            $options
        );

        return new IronMqProvider(
            'test',
            $options,
            $this->client,
            $this->getMock(
                'Doctrine\Common\Cache\PhpFileCache',
                [],
                ['/tmp', 'qpush.ironmq.test.php']
            ),
            $this->getMock(
                'Symfony\Bridge\Monolog\Logger',
                [],
                ['qpush.test']
            )
        );
    }

    public function testGetProviderReturnsTheNameOfTheProvider()
    {
        $provider = $this->provider->getProvider();

        $this->assertEquals('IronMQ', $provider);
    }

    public function testCreateWillCreateAQueue()
    {
        $this->assertFalse($this->provider->queueExists());
        $this->assertTrue($this->provider->create());
        $this->assertTrue($this->provider->queueExists());
    }

    public function testCreateFailsWithEmailTypeSubscriber()
    {
        $provider = $this->getIronMqProvider([
            'subscribers' => [
                [ 'protocol' => 'email', 'endpoint' => 'test@foo.com' ]
            ]
        ]);

        $this->setExpectedException('InvalidArgumentException', 'IronMQ only supports `http` or `https` subscribers!');
        $provider->create();
        $this->assertTrue($this->provider->queueExists());

    }

    public function testDestroyWillDestroyAQueue()
    {
        $this->provider->create();
        $this->assertTrue($this->provider->queueExists(), 'fail1');

        $this->assertTrue($this->provider->destroy(), 'fail2');

        $this->assertFalse($this->provider->queueExists(), 'fail3');

    }

    public function testPublishWillPublishAMessage()
    {
        $message = $this->provider->publish(['foo' => 'bar']);
        $this->assertInternalType("int", $message);
    }

    public function testReceiveWillReserveAndReturnAMessageFromTheQueue()
    {
        $this->provider = $this->getIronMqProvider(['push_notifications' => false]);
        $this->provider->publish(['baz' => 'bar']);

        $messages = $this->provider->receive();

        $this->assertInternalType('array', $messages);
        $this->assertCount(1, $messages);
        $this->assertEquals(['baz' => 'bar'], $messages[0]->getBody());
    }

    public function testDeleteAnUnreservedMessage()
    {
        $messageId = $this->provider->publish(['bat' => 'ball']);

        $this->assertTrue($this->provider->delete($messageId));
    }

    public function testDeleteAReservedMessage()
    {
        $this->provider = $this->getIronMqProvider(['push_notifications' => false]);
        $this->provider->publish(['Hello' => 'World']);
        $messages = $this->provider->receive();

        $this->assertTrue($this->provider->delete($messages[0]->getId()));
    }

    public function testOnNotification()
    {
        $event = new NotificationEvent(
            'test',
            NotificationEvent::TYPE_MESSAGE,
            new Notification(123, "test", [])
        );

        $this->provider->onNotification(
            $event,
            NotificationEvent::TYPE_MESSAGE,
            $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface')
        );
    }

    public function testOnMessageReceived()
    {
        $this->provider = $this->getIronMqProvider(['push_notifications' => false]);
        $this->provider->destroy();
        $this->provider->publish(['bob' => 'ball']);
        $messages = $this->provider->receive();

        $this->provider->onMessageReceived(new MessageEvent(
            'test',
            $messages[0]
        ));
    }

    public function testQueueInfo()
    {
        $this->provider->destroy();
        $this->assertNull($this->provider->queueInfo());

        $this->provider->create();
        $queue = $this->provider->queueInfo();
        $this->assertEquals('qpush_test', $queue->name);
        $this->assertEquals(IRONMQ_PROJECT_ID, $queue->project_id);
    }
}

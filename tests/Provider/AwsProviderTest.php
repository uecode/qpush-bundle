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

namespace Uecode\Bundle\QPushBundle\Tests\Provider;

use Aws\Common\Aws;
use Aws\Sqs\SqsClient;
use Doctrine\Common\Cache\Cache;
use Guzzle\Service\Resource\Model;
use Monolog\Logger;
use Uecode\Bundle\QPushBundle\Provider\AwsProvider;

use Uecode\Bundle\QPushBundle\Event\MessageEvent;
use Uecode\Bundle\QPushBundle\Event\NotificationEvent;

use Uecode\Bundle\QPushBundle\Message\Message;
use Uecode\Bundle\QPushBundle\Message\Notification;

use Uecode\Bundle\QPushBundle\Tests\MockClient\AwsMockClient;

/**
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class AwsProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Mock Client
     *
     * @var stdClass
     */
    protected $provider;

    public function setUp()
    {
        $this->provider = $this->getAwsProvider();
    }

    public function tearDown()
    {
        $this->provider = null;
    }

    private function getAwsProvider(array $options = [])
    {
        $options = array_merge(
            [
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
            ],
            $options
        );

        $client = new AwsMockClient([
            'key'       => '123_this_is_a_key',
            'secret'    => '123_this_is_a_secret',
            'region'    => 'us-east-1'
        ]);

        $cache = $this->getMock(
            'Doctrine\Common\Cache\PhpFileCache',
            [],
            ['/tmp', 'qpush.aws.test.php']
        );

        $logger = $this->getMock(
            'Symfony\Bridge\Monolog\Logger', [], ['qpush.test']
        );

        return new AwsProvider('test', $options, $client, $cache, $logger);
    }

    public function testGetProvider()
    {
        $provider = $this->provider->getProvider();

        $this->assertEquals('AWS', $provider);
    }

    public function testCreate()
    {
        //$this->assertFalse($this->provider->queueExists());

        $this->assertTrue($this->provider->create());
        $this->assertTrue($this->provider->queueExists());
    }

    public function testDestroy()
    {
        $this->assertTrue($this->provider->destroy());
    }

    public function testSqsPublish()
    {
        $provider = $this->getAwsProvider([
            'push_notifications'    => false
        ]);

        $this->assertEquals(123, $provider->publish(['foo' => 'bar']));
    }

    public function testSqsPublishFifo()
    {
        $mockedSqsClient = self::getMockBuilder(SqsClient::class)->disableOriginalConstructor()->setMethods(['sendMessage'])->getMock();

        // First, test that default values are set if no option overrides
        $mockedSqsClient->expects(self::at(0))
            ->method('sendMessage')
            ->with([
                    'QueueUrl' => null,
                    'MessageBody' => '{"this":"that"}',
                    'DelaySeconds' => 0,
                    'MessageGroupId' => 'qpush-group',
                    'MessageDeduplicationId' => md5('{"this":"that"}'),
            ])
            ->will(self::returnValue(new Model(['MessageId' => 456])));
        // And, test that that we can set overrides via options
        $mockedSqsClient->expects(self::at(1))
            ->method('sendMessage')
            ->with([
                'QueueUrl' => null,
                'MessageBody' => '{"this":"that"}',
                'DelaySeconds' => 0,
                'MessageGroupId' => 'override-group-id',
                'MessageDeduplicationId' => 'override-deduplication-id',
            ])
            ->will(self::returnValue(new Model(['MessageId' => 789])));

        $mockedAwsClient = self::getMockBuilder(Aws::class)->disableOriginalConstructor()->setMethods(['get'])->getMock();
        $mockedAwsClient->expects(self::at(0))
            ->method('get')
            ->with('Sqs')
            ->will(self::returnValue($mockedSqsClient));

        $mockedAwsProvider = self::getMockBuilder(AwsProvider::class)
            ->setConstructorArgs([
                'a_queue.fifo',
                [
                    'push_notifications' => false,
                    'message_delay' => 0,
                ],
                $mockedAwsClient,
                self::getMockBuilder(Cache::class)->disableOriginalConstructor()->getMock(),
                self::getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock(),
            ])
            ->setMethods(['queueExists', 'log'])
            ->getMock();

        $mockedAwsProvider->expects(self::any())
            ->method('queueExists')
            ->will(self::returnValue(true));
        $mockedAwsProvider->expects(self::any())
            ->method('log')
            ->will(self::returnValue(null));

        self::assertEquals(456, $mockedAwsProvider->publish(['this' => 'that'], []));
        self::assertEquals(789, $mockedAwsProvider->publish(['this' => 'that'], ['group_id' => 'override-group-id', 'deduplication_id' => 'override-deduplication-id']));
    }

    public function testSnsPublish()
    {
        $this->assertEquals(123, $this->provider->publish(['foo' => 'bar']));
    }

    public function testReceive()
    {
        $this->assertTrue(is_array($this->provider->receive()));
    }

    public function testDelete()
    {
        $provider = $this->getAwsProvider([
            'push_notifications' => false
        ]);

        $provider->createQueue();

        $this->assertTrue($provider->delete(123));
    }

    /**
     * @covers \Uecode\Bundle\QPushBundle\Provider\AwsProvider::createQueue
     * @covers \Uecode\Bundle\QPushBundle\Provider\AwsProvider::queueExists
     */
    public function testCreateQueue()
    {
        $provider = $this->getAwsProvider([
            'push_notifications' => false
        ]);

        $stub = $provider->getCache();
        $stub->expects($this->once())
             ->method('contains')
             ->will($this->returnValue(true));

        $this->assertTrue($provider->queueExists());

        $provider->createQueue();
        $this->assertTrue($provider->queueExists());

        $this->provider->createQueue();
        $this->assertTrue($this->provider->queueExists());
    }

    public function testCreateSqsPolicy()
    {
        $json_string = json_encode([
            'Version'   => '2008-10-17',
            'Id'        =>  sprintf('%s/SQSDefaultPolicy', "long_queue_arn_string"),
            'Statement' => [
                [
                    'Sid'       => 'SNSPermissions',
                    'Effect'    => 'Allow',
                    'Principal' => ['AWS' => '*'],
                    'Action'    => 'SQS:SendMessage',
                    'Resource'  => "long_queue_arn_string"
                ]
            ]
        ]);

        $this->assertJsonStringEqualsJsonString(
            $json_string,
            $this->provider->createSqsPolicy()
        );
    }

    /**
     * @covers \Uecode\Bundle\QPushBundle\Provider\AwsProvider::createTopic
     * @covers \Uecode\Bundle\QPushBundle\Provider\AwsProvider::topicExists
     */
    public function testCreateTopic()
    {
        $provider = $this->getAwsProvider();

        $this->assertFalse($provider->topicExists());

        $stub = $provider->getCache();
        $stub->expects($this->once())
             ->method('contains')
             ->will($this->returnValue(true));

        $this->assertTrue($provider->topicExists());

        $provider->createTopic();
        $this->assertTrue($provider->topicExists());

        $provider = $this->getAwsProvider(['push_notifications' => false]);
        $this->assertFalse($provider->createTopic());
    }

    public function testGetTopicSubscriptions()
    {
        $subscriptions  = $this->provider->getTopicSubscriptions("long_queue_arn_string");
        $expected       = [
            [
                'SubscriptionArn'   => 'long_subscription_arn_string',
                'Owner'             => 'owner_string',
                'Protocol'          => 'http',
                'Endpoint'          => 'http://long_url_string.com',
                'TopicArn'          => 'long_topic_arn_string'
            ]
        ];

        $this->assertEquals(
            $expected,
            $subscriptions
        );
    }

    public function testSubscribeToTopic()
    {
        $subscriptionArn = $this->provider->subscribeToTopic(
            'long_topic_arn_string',
            'http',
            'http://long_url_string.com'
        );

        $this->assertEquals('long_subscription_arn_string', $subscriptionArn);
    }

    public function testUnsubscribeFromTopic()
    {
        $this->assertTrue(
            $this->provider->unsubscribeFromTopic(
                'long_topic_arn_string',
                'http',
                'http://long_url_string.com'
            )
        );

        $this->assertFalse(
            $this->provider->unsubscribeFromTopic(
                'long_topic_arn_string',
                'http',
                'http://bad_long_url_string.com'
            )
        );
    }

    public function testOnNotificationSubscriptionEvent()
    {
        $dispatcher = $this->getMockForAbstractClass('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->provider->onNotification(new NotificationEvent(
            'test',
            NotificationEvent::TYPE_SUBSCRIPTION,
            new Notification(123, "test", [])
        ), NotificationEvent::TYPE_SUBSCRIPTION, $dispatcher);

    }

    public function testOnNotificationMessageEvent()
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
        $this->provider->onMessageReceived(new MessageEvent(
            'test',
            new Message(123, ['foo' => 'bar'], [])
        ));
    }
}

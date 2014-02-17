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

use Doctrine\Common\Cache\PhpFileCache;
use Symfony\Bridge\Monolog\Logger;

use Uecode\Bundle\QPushBundle\Provider\AwsProvider;

use Uecode\Bundle\QPushBundle\Event\MessageEvent;
use Uecode\Bundle\QPushBundle\Event\NotificationEvent;

use Uecode\Bundle\QPushBundle\Message\Message;
use Uecode\Bundle\QPushBundle\Message\Notification;

use Uecode\Bundle\QPushBundle\Tests\MockClient\AwsMockClient;

/**
 * AwsProviderTest
 *
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

    /**
     * @todo: Need to remove cache
     */
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
            'region'    => 'ue-east-1'
        ]);

        return new AwsProvider(
            'test', 
            $options,
            $client,
            new PhpFileCache('/tmp', mt_rand() . 'qpush.test.php'),
            new Logger('qpush.test')
        );
    }

    public function testGetProvider()
    {
        $provider = $this->provider->getProvider();

        $this->assertEquals('AWS', $provider);
    }

    public function testCreate()
    {
        $this->assertFalse($this->provider->queueExists());

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
     * @covers Uecode\Bundle\QPushBundle\Provider\AwsProvider::createQueue
     * @covers Uecode\Bundle\QPushBundle\Provider\AwsProvider::queueExists
     */
    public function testCreateQueue()
    {
        $provider = $this->getAwsProvider([
            'push_notifications' => false
        ]);

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
     * @covers Uecode\Bundle\QPushBundle\Provider\AwsProvider::createTopic
     * @covers Uecode\Bundle\QPushBundle\Provider\AwsProvider::topicExists
     */
    public function testCreatePolicy()
    {
        $provider = $this->getAwsProvider();

        $this->assertFalse($provider->topicExists());

        $provider->createTopic();
        $this->assertTrue($provider->topicExists());
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
        $unsubscribed = $this->provider->unsubscribeFromTopic(
            'long_topic_arn_string',
            'http',
            'http://long_url_string.com'
        );

        $this->assertTrue($unsubscribed);
    }

    public function testOnNotification()
    {
        $this->provider->onNotification(new NotificationEvent(
            'test',
            NotificationEvent::TYPE_SUBSCRIPTION,
            new Notification(123, "test", [])
        ));
    }

    public function testOnMessageReceived()
    {
        $this->provider->onMessageReceived(new MessageEvent(
            'test',
            new Message(123, ['foo' => 'bar'], [])
        ));
    }
}

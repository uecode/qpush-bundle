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

use Uecode\Bundle\QPushBundle\Provider\ProviderInterface;

use Uecode\Bundle\QPushBundle\Event\MessageEvent;
use Uecode\Bundle\QPushBundle\Event\NotificationEvent;

use Uecode\Bundle\QPushBundle\Message\Message;
use Uecode\Bundle\QPushBundle\Message\Notification;

/**
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class AbstractProviderTest extends \PHPUnit_Framework_TestCase
{
    protected $provider;

    public function setUp()
    {
        $this->provider = $this->getTestProvider();
    }

    public function tearDown()
    {
        $this->provider = null;

        if (file_exists('/tmp/qpush.provider.test.php')) {
            unlink('/tmp/qpush.provider.test.php');
        }
    }

    private function getTestProvider(array $options = [])
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

        return new TestProvider(
            'test',
            $options,
            new \stdClass,
            $this->getMock(
                'Doctrine\Common\Cache\PhpFileCache',
                [],
                ['/tmp', 'qpush.aws.test.php']
            ),
            $this->getMock(
                'Symfony\Bridge\Monolog\Logger',
                [],
                ['qpush.test']
            )
        );
    }

    public function testGetName()
    {
        $name = $this->provider->getName();

        $this->assertEquals($name, 'test');
    }

    public function testGetNameWithPrefix()
    {
        $name = $this->provider->getNameWithPrefix();

        $this->assertEquals(sprintf('%s_%s', ProviderInterface::QPUSH_PREFIX, 'test'), $name);
    }

    public function testGetNameWithPrefixProvidedName()
    {
        $provider = $this->getTestProvider(['queue_name' => 'foo']);
        $name = $provider->getNameWithPrefix();

        $this->assertEquals('foo', $name);
    }

    public function testGetOptions()
    {
        $options = $this->provider->getOptions();

        $this->assertTrue(is_array($options));
    }

    public function testGetCache()
    {
        $cache = $this->provider->getCache();

        $this->assertInstanceOf('Doctrine\\Common\\Cache\\Cache', $cache);
    }

    public function testGetLogger()
    {
        $logger = $this->provider->getLogger();

        $this->assertInstanceOf('Monolog\\Logger', $logger);
    }

    public function testLogEnabled()
    {
        $this->assertFalse($this->provider->log(100, 'test log', []));

        $provider = $this->getTestProvider(['logging_enabled' => true]);

        $this->assertNull($provider->log(100, 'test log', []));
    }

    public function testGetProvider()
    {
        $provider = $this->provider->getProvider();

        $this->assertEquals('TestProvider', $provider);
    }

    public function testOnNotification()
    {
        $dispatcher = $this->getMockForAbstractClass('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $result = $this->provider->onNotification(new NotificationEvent(
            'test',
            NotificationEvent::TYPE_SUBSCRIPTION,
            new Notification(123, "test", [])
        ), NotificationEvent::TYPE_SUBSCRIPTION, $dispatcher);

        $this->assertFalse($result);
    }

    public function testOnMessageReceived()
    {
        $result = $this->provider->onMessageReceived(new MessageEvent(
            'test',
            new Message(123, ['foo' => 'bar'], [])
        ));

        $this->assertFalse($result);
    }

    public function testMergeOptions()
    {
        $options = ['message_delay' => 1, 'not_an_option' => false];
        $merged  = $this->provider->mergeOptions($options);

        $this->assertTrue($merged['message_delay'] === 1);
        $this->assertFalse(isset($merged['not_an_option']));
    }
}

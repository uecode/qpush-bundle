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

namespace Uecode\Bundle\QPushBundle\Tests\Event;

use Uecode\Bundle\QPushBundle\Event\NotificationEvent;

use Uecode\Bundle\QPushBundle\Message\Notification;

/**
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class NotificationEventTest extends \PHPUnit_Framework_TestCase
{
    protected $event;

    public function setUp()
    {
        $this->event = new NotificationEvent(
            'test',
            NotificationEvent::TYPE_SUBSCRIPTION,
            new Notification(123, ['foo' => 'bar'], ['bar' => 'baz'])
        );
    }

    public function tearDown()
    {
        $this->event = null;
    }

    public function testNotificationEventConstructor()
    {
        $event = new NotificationEvent(
            'test',
            NotificationEvent::TYPE_SUBSCRIPTION,
            new Notification(123, ['foo' => 'bar'], ['bar' => 'baz'])
        );
        $this->assertInstanceOf('Uecode\Bundle\QPushBundle\Event\NotificationEvent', $event);

        $event = new NotificationEvent(
            'test',
            NotificationEvent::TYPE_MESSAGE,
            new Notification(123, ['foo' => 'bar'], ['bar' => 'baz'])
        );
        $this->assertInstanceOf('Uecode\Bundle\QPushBundle\Event\NotificationEvent', $event);

        $this->setExpectedException('InvalidArgumentException');
        $event = new NotificationEvent(
            'test',
            'InvalidNotificationType',
            new Notification(123, ['foo' => 'bar'], ['bar' => 'baz'])
        );

        $this->setExpectedException('PHPUnit_Framework_Error');
        $event = new NotificationEvent(
            'test',
            NotificationEvent::TYPE_SUBSCRIPTION,
            ['bad argument']
        );
    }

    public function testGetQueueName()
    {
        $name = $this->event->getQueueName();

        $this->assertEquals('test', $name);
    }

    public function testGetType()
    {
        $type = $this->event->getType();

        $this->assertContains(
            $type,
            [
                NotificationEvent::TYPE_SUBSCRIPTION,
                NotificationEvent::TYPE_MESSAGE
            ]
        );
    }

    public function testGetNotification()
    {
        $notification = $this->event->getNotification();

        $this->assertInstanceOf('Uecode\Bundle\QPushBundle\Message\Notification', $notification);
    }
}

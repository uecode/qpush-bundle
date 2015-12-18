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

use Uecode\Bundle\QPushBundle\Event\MessageEvent;

use Uecode\Bundle\QPushBundle\Message\Message;

/**
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class MessageEventTest extends \PHPUnit_Framework_TestCase
{
    protected $event;

    public function setUp()
    {
        $this->event = new MessageEvent('test', new Message(123, ['foo' => 'bar'], ['bar' => 'baz']));
    }

    public function tearDown()
    {
        $this->event = null;
    }

    public function testMessageEventConstructor()
    {
        $event = new MessageEvent('test', new Message(123, ['foo' => 'bar'], ['bar' => 'baz']));
        $this->assertInstanceOf('Uecode\Bundle\QPushBundle\Event\MessageEvent', $event);

        if (version_compare(PHP_VERSION, '7.0', '>=')) {
            $this->setExpectedException('TypeError');
        } else {
            $this->setExpectedException('PHPUnit_Framework_Error');
        }

        $event = new MessageEvent('test', ['bad argument']);
    }

    public function testGetQueueName()
    {
        $name = $this->event->getQueueName();

        $this->assertEquals('test', $name);
    }

    public function testGetMessage()
    {
        $message = $this->event->getMessage();

        $this->assertInstanceOf('Uecode\Bundle\QPushBundle\Message\Message', $message);
    }
}

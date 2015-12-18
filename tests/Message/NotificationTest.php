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

namespace Uecode\Bundle\QpushBundle\Tests\Message;

use Uecode\Bundle\QPushBundle\Message\Notification;

/**
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class NotificationTest extends BaseMessageTest
{
    public function setUp()
    {
        $this->message = new Notification(123, ['foo' => 'bar'], ['baz' => 'qux']);
    }

    public function tearDown()
    {
        $this->message = null;
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testConstructor()
    {
        $notification = new Notification(123, ['foo' => 'bar'], ['baz' => 'qux']);
        $this->assertInstanceOf('Uecode\Bundle\QPushBundle\Message\Notification', $notification);

        if (version_compare(PHP_VERSION, '7.0', '>=')) {
            $this->setExpectedException('TypeError');
        } else {
            $this->setExpectedException('PHPUnit_Framework_Error');
        }
        
        new Notification(123, ['foo' => 'bar'], 'invalid argument');
    }
}

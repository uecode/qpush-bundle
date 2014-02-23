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

use Uecode\Bundle\QPushBundle\Event\Events;

/**
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class EventsTest extends \PHPUnit_Framework_TestCase
{
    public function testConstants()
    {
        $this->assertEquals('message_received', Events::ON_MESSAGE);
        $this->assertEquals('on_notification', Events::ON_NOTIFICATION);
    }

    public function testMessageEvent()
    {
        $event = Events::Message('test');

        $this->assertEquals(sprintf('%s.%s', 'test', Events::ON_MESSAGE), $event);
    }

    public function testNotificationEvent()
    {
        $event = Events::Notification('test');

        $this->assertEquals(sprintf('%s.%s', 'test', Events::ON_NOTIFICATION), $event);
    }
}

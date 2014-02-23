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

namespace Uecode\Bundle\QPushBundle\Event;

/**
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
abstract class Events
{
    const ON_NOTIFICATION  = 'on_notification';
    const ON_MESSAGE       = 'message_received';

    /**
     * @codeCoverageIgnore
     */
    final private function __construct() { }

    /**
     * Returns a QPush Notification Event Name
     *
     * @param string $name The name of the Queue for this Event
     *
     * @return string
     */
    public static function Notification($name)
    {
        return sprintf('%s.%s', $name, self::ON_NOTIFICATION);
    }

    /**
     * Returns a QPush Notification Event Name
     *
     * @param string $name The name of the Queue for this Event
     *
     * @return string
     */
    public static function Message($name)
    {
        return sprintf('%s.%s', $name, self::ON_MESSAGE);
    }
}

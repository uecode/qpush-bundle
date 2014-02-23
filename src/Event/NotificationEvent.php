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

use Symfony\Component\EventDispatcher\Event;
use Uecode\Bundle\QPushBundle\Message\Notification;

/**
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class NotificationEvent extends Event
{
    /**
     * A Subscription Notification Type
     */
    const TYPE_SUBSCRIPTION  = 'SubscriptionNotification';
    /**
     * A Message Notification Type
     */
    const TYPE_MESSAGE       = 'MessageNotification';

    /**
     * Queue name
     *
     * @var string
     */
    protected $queueName;

    /**
     * Notification Type
     *
     * @var string
     */
    protected $type;

    /**
     * Notification
     *
     * @var array
     */
    protected $notification;

    /**
     * Constructor
     *
     * @param string       $queueName    The Queue Name
     * @param string       $type         The Notification Type
     * @param Notification $notification The Notification
     */
    public function __construct($queueName, $type, Notification $notification)
    {
        if (!in_array($type, [self::TYPE_SUBSCRIPTION, self::TYPE_MESSAGE])) {
            throw new \InvalidArgumentException(
                sprintf("Invalid notification type given! (%s)", $type)
            );
        }

        $this->queueName    = $queueName;
        $this->type         = $type;
        $this->notification = $notification;
    }

    /**
     * Returns the Queue name
     *
     * return string
     */
    public function getQueueName()
    {
        return $this->queueName;
    }

    /**
     * Returns the Notification Type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns the Notification
     *
     * return array
     */
    public function getNotification()
    {
        return $this->notification;
    }
}

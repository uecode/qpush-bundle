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
use Uecode\Bundle\QpushBundle\Message\Message;

/**
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class MessageEvent extends Event
{
    /**
     * Queue name
     *
     * @var string
     */
    protected $queueName;

    /**
     * Message
     *
     * @var mixed
     */
    protected $message;

    /**
     * Constructor.
     *
     * @param string  $queueName   The queue name
     * @param Message $message The Message
     */
    public function __construct($queueName, Message $message)
    {
        $this->queueName    = $queueName;
        $this->message      = $message;
    }

    /**
     * Return the SQS Queue Name
     *
     * @return string
     */
    public function getQueueName()
    {
        return $this->queueName;
    }

    /**
     * Return the Full SQS Message
     *
     * @return Message
     */
    public function getMessage()
    {
        return $this->message;
    }
}

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

namespace Uecode\Bundle\QPushBundle\Tests\MockClient;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @codeCoverageIgnore
 *
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class SqsMockClient
{
    public function getQueueArn($url)
    {
        return "long_queue_arn_string";
    }

    public function getQueueUrl($name)
    {
        return new ArrayCollection([
            'QueueUrl' => 'long_queue_url_string'
        ]);
    }

    public function deleteQueue(array $args)
    {
        return true;
    }

    public function sendMessage(array $args)
    {
        return new ArrayCollection([
            'MessageId' => 123
        ]);
    }

    public function receiveMessage(array $args)
    {
        return new ArrayCollection([
            'Messages' => [
                [
                    'MessageId'     => 123,
                    'ReceiptHandle' => 'long_receipt_handle_string',
                    'MD5OfBody'     => 'long_md5_hash_string',
                    'Body'          => json_encode(['foo' => 'bar'])
                ],
                [
                    'MessageId'     => 123,
                    'ReceiptHandle' => 'long_receipt_handle_string',
                    'MD5OfBody'     => 'long_md5_hash_string',
                    'Body'          => json_encode(['Message' => json_encode(['foo' => 'bar'])])
                ]
            ]
        ]);
    }

    public function deleteMessage(array $args)
    {
        return true;
    }

    public function createQueue(array $args)
    {
        return new ArrayCollection([
            'QueueUrl' => 'long_queue_url_string'
        ]);
    }

    public function setQueueAttributes(array $args)
    {
        return true;
    }
}

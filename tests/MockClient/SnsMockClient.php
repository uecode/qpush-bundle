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

use Aws\Sns\Exception\NotFoundException;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @codeCoverageIgnore
 *
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class SnsMockClient
{
    public function deleteTopic(array $args)
    {
        return true;
    }

    public function publish(array $args)
    {
        return new ArrayCollection([
            'MessageId' => 123
        ]);
    }

    public function createTopic(array $args)
    {
        return new ArrayCollection([
            'TopicArn' => 'long_topic_arn_string'
        ]);
    }

    public function getTopicAttributes(array $args)
    {
        if ($args['TopicArn'] == null) {
            throw new NotFoundException;
        }

        return new ArrayCollection([
            'Attributes' => [
                'TopicArn' => 'long_topic_arn_string'
            ]
        ]);
    }

    public function listSubscriptionsByTopic(array $args)
    {
        return new ArrayCollection([
            'Subscriptions' => [
                [
                    'SubscriptionArn'   => 'long_subscription_arn_string',
                    'Owner'             => 'owner_string',
                    'Protocol'          => 'http',
                    'Endpoint'          => 'http://long_url_string.com',
                    'TopicArn'          => 'long_topic_arn_string'
                ]
            ]
        ]);
    }

    public function subscribe(array $args)
    {
        return new ArrayCollection([
            'SubscriptionArn' => 'long_subscription_arn_string'
        ]);
    }

    public function unsubscribe(array $args)
    {
        return true;
    }

    public function confirmSubscription(array $args)
    {
        return true;
    }
}

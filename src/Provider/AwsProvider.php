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

namespace Uecode\Bundle\QPushBundle\Provider;

use Aws\Sns\Exception\NotFoundException;
use Aws\Sns\SnsClient;
use Aws\Sqs\Exception\SqsException;
use Aws\Sqs\SqsClient;

use Doctrine\Common\Cache\Cache;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Uecode\Bundle\QPushBundle\Event\Events;
use Uecode\Bundle\QPushBundle\Event\MessageEvent;
use Uecode\Bundle\QPushBundle\Event\NotificationEvent;
use Uecode\Bundle\QPushBundle\Message\Message;

/**
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class AwsProvider extends AbstractProvider
{
    /**
     * Aws SQS Client
     *
     * @var SqsClient
     */
    private $sqs;

    /**
     * Aws SNS Client
     *
     * @var SnsClient
     */
    private $sns;

    /**
     * SQS Queue URL
     *
     * @var string
     */
    private $queueUrl;

    /**
     * SNS Topic ARN
     *
     * @var string
     */
    private $topicArn;

    public function __construct($name, array $options, $client, Cache $cache, Logger $logger)
    {
        $this->name     = $name;
        $this->options  = $options;
        $this->cache    = $cache;
        $this->logger   = $logger;

        // get() method used for sdk v2, create methods for v3
        $useGet = method_exists($client, 'get');
        $this->sqs = $useGet ? $client->get('Sqs') : $client->createSqs();
        $this->sns = $useGet ? $client->get('Sns') : $client->createSns();
    }

    public function getProvider()
    {
        return "AWS";
    }

    /**
     * Builds the configured queues
     *
     * If a Queue name is passed and configured, this method will build only that
     * Queue.
     *
     * All Create methods are idempotent, if the resource exists, the current ARN
     * will be returned
     *
     */
    public function create()
    {
        $this->createQueue();

        if ($this->options['push_notifications']) {
           // Create the SNS Topic
           $this->createTopic();

           // Add the SQS Queue as a Subscriber to the SNS Topic
           $this->subscribeToTopic(
               $this->topicArn,
               'sqs',
               $this->sqs->getQueueArn($this->queueUrl)
           );

           // Add configured Subscribers to the SNS Topic
           foreach ($this->options['subscribers'] as $subscriber) {
                $this->subscribeToTopic(
                    $this->topicArn,
                    $subscriber['protocol'],
                    $subscriber['endpoint']
                );
            }
        }

        return true;
    }

    /**
     * @return Boolean
     */
    public function destroy()
    {
        $key = $this->getNameWithPrefix() . '_url';
        $this->cache->delete($key);

        if ($this->queueExists()) {
            // Delete the SQS Queue
            $this->sqs->deleteQueue([
                'QueueUrl' => $this->queueUrl
            ]);

            $this->log(200,"SQS Queue removed", ['QueueUrl' => $this->queueUrl]);
        }

        $key = $this->getNameWithPrefix() . '_arn';
        $this->cache->delete($key);

        if ($this->topicExists() || !empty($this->queueUrl)) {
            // Delete the SNS Topic
            $topicArn = !empty($this->topicArn)
                ? $this->topicArn
                : str_replace('sqs', 'sns', $this->queueUrl)
            ;

            $this->sns->deleteTopic([
                'TopicArn' => $topicArn
            ]);

            $this->log(200,"SNS Topic removed", ['TopicArn' => $topicArn]);
        }

        return true;
    }

    /**
     * {@inheritDoc}
     *
     * This method will either use a SNS Topic to publish a queued message or
     * straight to SQS depending on the application configuration.
     *
     * @return string
     */
    public function publish(array $message, array $options = [])
    {
        $options      = $this->mergeOptions($options);
        $publishStart = microtime(true);

        // ensures that the SQS Queue and SNS Topic exist
        if (!$this->queueExists()) {
            $this->create();
        }

        if ($options['push_notifications']) {

            if (!$this->topicExists()) {
                $this->create();
            }

            $message    = [
                'default'   => $this->getNameWithPrefix(),
                'sqs'       => json_encode($message),
                'http'      => $this->getNameWithPrefix(),
                'https'     => $this->getNameWithPrefix(),
            ];

            $result = $this->sns->publish([
                'TopicArn'          => $this->topicArn,
                'Subject'           => $this->getName(),
                'Message'           => json_encode($message),
                'MessageStructure'  => 'json'
            ]);

            $context = [
                'TopicArn'              => $this->topicArn,
                'MessageId'             => $result->get('MessageId'),
                'push_notifications'    => $options['push_notifications'],
                'publish_time'          => microtime(true) - $publishStart
            ];
            $this->log(200,"Message published to SNS", $context);

            return $result->get('MessageId');
        }

        $result = $this->sqs->sendMessage([
            'QueueUrl'      => $this->queueUrl,
            'MessageBody'   => json_encode($message),
            'DelaySeconds'  => $options['message_delay']
        ]);

        $context = [
            'QueueUrl'              => $this->queueUrl,
            'MessageId'             => $result->get('MessageId'),
            'push_notifications'    => $options['push_notifications']
        ];
        $this->log(200,"Message published to SQS", $context);

        return $result->get('MessageId');
    }

    /**
     * {@inheritDoc}
     */
    public function receive(array $options = [])
    {
        $options = $this->mergeOptions($options);

        if (!$this->queueExists()) {
            $this->create();
        }

        $result = $this->sqs->receiveMessage([
            'QueueUrl'              => $this->queueUrl,
            'MaxNumberOfMessages'   => $options['messages_to_receive'],
            'WaitTimeSeconds'       => $options['receive_wait_time']
        ]);

        $messages = $result->get('Messages') ?: [];

        // Convert to Message Class
        foreach ($messages as &$message) {
            $id = $message['MessageId'];
            $metadata = [
                'ReceiptHandle' => $message['ReceiptHandle'],
                'MD5OfBody'     => $message['MD5OfBody']
            ];

            // When using SNS, the SQS Body is the entire SNS Message
            if(is_array($body = json_decode($message['Body'], true))
                && isset($body['Message'])
            ) {
                $body = json_decode($body['Message'], true);
            }

            $message = new Message($id, $body, $metadata);

            $context = ['MessageId' => $id];
            $this->log(200,"Message fetched from SQS Queue", $context);

        }

        return $messages;
    }

    /**
     * {@inheritDoc}
     *
     * @return bool
     */
    public function delete($id)
    {
        if (!$this->queueExists()) {
            return false;
        }

        $this->sqs->deleteMessage([
            'QueueUrl'      => $this->queueUrl,
            'ReceiptHandle' => $id
        ]);

        $context = [
            'QueueUrl'      => $this->queueUrl,
            'ReceiptHandle' => $id
        ];
        $this->log(200,"Message deleted from SQS Queue", $context);

        return true;
    }

    /**
     * Return the Queue Url
     *
     * This method relies on in-memory cache and the Cache provider
     * to reduce the need to needlessly call the create method on an existing
     * Queue.
     *
     * @return boolean
     */
    public function queueExists()
    {
        if (isset($this->queueUrl)) {
            return true;
        }

        $key = $this->getNameWithPrefix() . '_url';
        if ($this->cache->contains($key)) {
            $this->queueUrl = $this->cache->fetch($key);

            return true;
        }

        try {
            $result = $this->sqs->getQueueUrl([
                'QueueName' => $this->getNameWithPrefix()
            ]);

            if ($this->queueUrl = $result->get('QueueUrl')) {
                $this->cache->save($key, $this->queueUrl);

                return true;
            }
        } catch (SqsException $e) {}

        return false;
    }

    /**
     * Creates an SQS Queue and returns the Queue Url
     *
     * The create method for SQS Queues is idempotent - if the queue already
     * exists, this method will return the Queue Url of the existing Queue.
     *
     * @return string
     */
    public function createQueue()
    {
        $result = $this->sqs->createQueue([
            'QueueName' => $this->getNameWithPrefix(),
            'Attributes'    => [
                'VisibilityTimeout'             => $this->options['message_timeout'],
                'MessageRetentionPeriod'        => $this->options['message_expiration'],
                'ReceiveMessageWaitTimeSeconds' => $this->options['receive_wait_time']
            ]
        ]);

        $this->queueUrl = $result->get('QueueUrl');

        $key = $this->getNameWithPrefix() . '_url';
        $this->cache->save($key, $this->queueUrl);

        $this->log(200, "Created SQS Queue", ['QueueUrl' => $this->queueUrl]);

        if ($this->options['push_notifications']) {

            $policy = $this->createSqsPolicy();

            $this->sqs->setQueueAttributes([
                'QueueUrl'      => $this->queueUrl,
                'Attributes'    => [
                    'Policy'    => $policy,
                ]
            ]);

            $this->log(200, "Created Updated SQS Policy");
        }
    }

    /**
     * Creates a Policy for SQS that's required to allow SNS SendMessage access
     *
     * @return string
     */
    public function createSqsPolicy()
    {
        $arn = $this->sqs->getQueueArn($this->queueUrl);

        return json_encode([
            'Version'   => '2008-10-17',
            'Id'        =>  sprintf('%s/SQSDefaultPolicy', $arn),
            'Statement' => [
                [
                    'Sid'       => 'SNSPermissions',
                    'Effect'    => 'Allow',
                    'Principal' => ['AWS' => '*'],
                    'Action'    => 'SQS:SendMessage',
                    'Resource'  => $arn
                ]
            ]
        ]);
    }

    /**
     * Checks to see if a Topic exists
     *
     * This method relies on in-memory cache and the Cache provider
     * to reduce the need to needlessly call the create method on an existing
     * Topic.
     *
     * @return boolean
     */
    public function topicExists()
    {
        if (isset($this->topicArn)) {
            return true;
        }

        $key = $this->getNameWithPrefix() . '_arn';
        if ($this->cache->contains($key)) {
            $this->topicArn = $this->cache->fetch($key);

            return true;
        }

        if (!empty($this->queueUrl)) {
            $queueArn = $this->sqs->getQueueArn($this->queueUrl);
            $topicArn = str_replace('sqs', 'sns', $queueArn);

            try {
                $this->sns->getTopicAttributes([
                    'TopicArn' => $topicArn
                ]);
            } catch (NotFoundException $e) {
                return false;
            }

            $this->topicArn = $topicArn;
            $this->cache->save($key, $this->topicArn);

            return true;
        }

        return false;
    }

    /**
     * Creates a SNS Topic and returns the ARN
     *
     * The create method for the SNS Topics is idempotent - if the topic already
     * exists, this method will return the Topic ARN of the existing Topic.
     *
     *
     * @return false|null
     */
    public function createTopic()
    {
        if (!$this->options['push_notifications']) {
            return false;
        }

        $result = $this->sns->createTopic([
            'Name' => $this->getNameWithPrefix()
        ]);

        $this->topicArn = $result->get('TopicArn');

        $key = $this->getNameWithPrefix() . '_arn';
        $this->cache->save($key, $this->topicArn);

        $this->log(200, "Created SNS Topic", ['TopicARN' => $this->topicArn]);
    }

    /**
     * Get a list of Subscriptions for the specified SNS Topic
     *
     * @param string $topicArn The SNS Topic Arn
     *
     * @return array
     */
    public function getTopicSubscriptions($topicArn)
    {
        $result = $this->sns->listSubscriptionsByTopic([
            'TopicArn' => $topicArn
        ]);

        return $result->get('Subscriptions');
    }

    /**
     * Subscribes an endpoint to a SNS Topic
     *
     * @param string $topicArn The ARN of the Topic
     * @param string $protocol The protocol of the Endpoint
     * @param string $endpoint The Endpoint of the Subscriber
     *
     * @return string
     */
    public function subscribeToTopic($topicArn, $protocol, $endpoint)
    {
        // Check against the current Topic Subscriptions
        $subscriptions = $this->getTopicSubscriptions($topicArn);
        foreach ($subscriptions as $subscription) {
            if ($endpoint === $subscription['Endpoint']) {
                return $subscription['SubscriptionArn'];
            }
        }

        $result = $this->sns->subscribe([
            'TopicArn' => $topicArn,
            'Protocol' => $protocol,
            'Endpoint' => $endpoint
        ]);

        $arn = $result->get('SubscriptionArn');

        $context = [
            'Endpoint' => $endpoint,
            'Protocol' => $protocol,
            'SubscriptionArn' => $arn
        ];
        $this->log(200, "Endpoint Subscribed to SNS Topic", $context);

        return $arn;
    }

    /**
     * Unsubscribes an endpoint from a SNS Topic
     *
     * The method will return TRUE on success, or FALSE if the Endpoint did not
     * have a Subscription on the SNS Topic
     *
     * @param string $topicArn The ARN of the Topic
     * @param string $protocol The protocol of the Endpoint
     * @param string $endpoint The Endpoint of the Subscriber
     *
     * @return Boolean
     */
    public function unsubscribeFromTopic($topicArn, $protocol, $endpoint)
    {
        // Check against the current Topic Subscriptions
        $subscriptions = $this->getTopicSubscriptions($topicArn);
        foreach ($subscriptions as $subscription) {
            if ($endpoint === $subscription['Endpoint']) {
                $this->sns->unsubscribe([
                    'SubscriptionArn' => $subscription['SubscriptionArn']
                ]);

                $context = [
                    'Endpoint' => $endpoint,
                    'Protocol' => $protocol,
                    'SubscriptionArn' => $subscription['SubscriptionArn']
                ];
                $this->log(200,"Endpoint unsubscribed from SNS Topic", $context);

                return true;
            }
        }

        return false;
    }

    /**
     * Handles SNS Notifications
     *
     * For Subscription notifications, this method will automatically confirm
     * the Subscription request
     *
     * For Message notifications, this method polls the queue and dispatches
     * the `{queue}.message_received` event for each message retrieved
     *
     * @param NotificationEvent $event The Notification Event
     * @param string $eventName Name of the event
     * @param EventDispatcherInterface $dispatcher
     * @return bool|void
     */
    public function onNotification(NotificationEvent $event, $eventName, EventDispatcherInterface $dispatcher)
    {
        if (NotificationEvent::TYPE_SUBSCRIPTION == $event->getType()) {
            $topicArn   = $event->getNotification()->getMetadata()->get('TopicArn');
            $token      = $event->getNotification()->getMetadata()->get('Token');

            $this->sns->confirmSubscription([
                'TopicArn'  => $topicArn,
                'Token'     => $token
            ]);

            $context = ['TopicArn' => $topicArn];
            $this->log(200,"Subscription to SNS Confirmed", $context);

            return;
        }

        $messages = $this->receive();
        foreach ($messages as $message) {

            $messageEvent = new MessageEvent($this->name, $message);
            $dispatcher->dispatch(Events::Message($this->name), $messageEvent);
        }
    }

    /**
     * Removes the message from queue after all other listeners have fired
     *
     * If an earlier listener has erred or stopped propagation, this method
     * will not fire and the Queued Message should become visible in queue again.
     *
     * Stops Event Propagation after removing the Message
     *
     * @param MessageEvent $event The SQS Message Event
     * @return bool|void
     */
    public function onMessageReceived(MessageEvent $event)
    {
        $receiptHandle = $event
            ->getMessage()
            ->getMetadata()
            ->get('ReceiptHandle');

        $this->delete($receiptHandle);

        $event->stopPropagation();
    }
}

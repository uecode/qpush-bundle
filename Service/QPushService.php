<?php

namespace Uecode\Bundle\QPushBundle\Service;

use Doctrine\Common\Cache\Cache;

use Aws\Sqs\SqsClient;
use Aws\Sns\SnsClient;

use Uecode\Bundle\QPushBundle\Event\Events;
use Uecode\Bundle\QPushBundle\Event\MessageEvent;
use Uecode\Bundle\QPushBundle\Event\NotificationEvent;
use Uecode\Bundle\QPushBundle\Event\SubscriptionEvent;
use Uecode\Bundle\QPushBundle\EventListener\MessageListener;
use Uecode\Bundle\QPushBundle\EventListener\NotificationListener;
use Uecode\Bundle\QPushBundle\EventListener\SubscriptionListener;

class QPushService implements MessageListener, NotificationListener, SubscriptionListener
{
    const QPUSH_PREFIX = 'uecode_qpush_';

    /**
     * QPush Queue Name
     *
     * @var string
     */
    private $name;

    /**
     * QPush Queue Options
     *
     * @var array
     */
    private $options;

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

    /**
     * Doctrine APC Cache Driver
     *
     * @var Cache
     */
    private $cache;

    /**
     * AWS SQS Client
     *
     * @var SqsClient
     */
    private $sqsClient;

    /**
     * AWS SNS Client
     *
     * @var SnsClient
     */
    private $snsClient;

    /**
     * Constructor.
     *
     * @param string    $name    Queue name
     * @param array     $options Queue Options
     *                           $param Cache     $cache  A Doctrine Cache Providier
     * @param SqsClient $sqs     An AWS SQS Client
     * @param SnsClient $sns     An AWS SNS Client
     */
    public function __construct($name, array $options, Cache $cache, SqsClient $sqs, SnsClient $sns)
    {
        $this->name         = $name;
        $this->options      = $options;
        $this->cache        = $cache;
        $this->sqsClient    = $sqs;
        $this->snsClient    = $sns;
    }

    /**
     * Returns the Queue Name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the Queue Name prefixed with the QPush namespace
     *
     * @return string
     */
    public function getNameWithPrefix()
    {
        return self::QPUSH_PREFIX . $this->name;
    }

    /**
     * Returns the Queue's SQS Options
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Pushes a message to the Queue
     *
     * This method will either use a SNS Topic to publish a queued message or
     * straight to SQS depending on the application configuration.
     *
     * @param array $message The message to queue
     *
     * @return string
     */
    public function push(array $message)
    {
        if ($this->options['use_sns']) {

            $message    = [
                'default'   => $this->getNameWithPrefix(),
                'sqs'       => json_encode($message),
                'http'      => $this->getNameWithPrefix(),
                'https'     => $this->getNameWithPrefix(),
            ];

            $result = $this->snsClient->publish([
                'TopicArn'          => $this->getTopicArn(),
                'Subject'           => $this->getNameWithPrefix(),
                'Message'           => json_encode($message),
                'MessageStructure'  => 'json'
            ]);

            return $result->get('MessageId');
        }

        $result = $this->sqsClient->sendMessage([
            'QueueUrl'      => $this->getQueueUrl(),
            'MessageBody'   => json_encode($message),
            'DelaySeconds'  => $this->options['delay_seconds']
        ]);

        return $result->get('MessageId');
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
    public function build()
    {
        // Create the SQS Queue
        $queueUrl = $this->createQueue();

        if ($this->options['use_sns']) {
           // Create the SNS Topic
           $topicArn = $this->createTopic();

           // Add the SQS Queue as a Subscriber to the SNS Topic
           $this->subscribeEndpoint(
               $topicArn,
               'sqs',
               $this->sqsClient->getQueueArn($queueUrl)
           );

           // Add configured Subscribers to the SNS Topic
           foreach ($this->options['subscribers'] as $subscriber) {
                $this->subscribeEndpoint(
                    $topicArn,
                    $subscriber['protocol'],
                    $subscriber['endpoint']
                );
            }
        }
    }

    /**
     * Polls the Queue for Messages
     *
     * The `receiveMessage` method will remain open for the configured
     * `received_message_wait_time_seconds` value on this queue, to allow for
     * long polling.
     *
     * @return array
     */
    public function pollQueue()
    {
        $result = $this->sqsClient->receiveMessage([
            'QueueUrl'          => $this->getQueueUrl(),
            'WaitTimeSeconds'   => $this->options['receive_message_wait_time_seconds']
        ]);

        return $result->get('Messages') ?: [];
    }

    /**
     * Return the Queue Url
     *
     * This method expects that the Queue was created by this service and appends
     * a prefix to the queue name
     *
     * @return string
     */
    public function getQueueUrl()
    {
        if (!empty($this->queueUrl)) {
            return $this->queueUrl;
        }

        $urlKey = $this->getNameWithPrefix() . '_url';
        if ($this->cache->contains($urlKey)) {
            return $this->queueUrl = $this->cache->fetch($urlKey);
        }

        return $this->createQueue();
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
        $result = $this->sqsClient->createQueue([
            'QueueName'     => $this->getNameWithPrefix(),
            'Attributes'    => [
                'DelaySeconds'                  => $this->options['delay_seconds'],
                'MaximumMessageSize'            => $this->options['maximum_message_size'],
                'MessageRetentionPeriod'        => $this->options['message_retention_period'],
                'VisibilityTimeout'             => $this->options['visibility_timeout'],
                'ReceiveMessageWaitTimeSeconds' => $this->options['receive_message_wait_time_seconds']
            ]
        ]);

        $this->queueUrl = $result->get('QueueUrl');

        $urlKey = $this->getNameWithPrefix() . '_url';
        $this->cache->save($urlKey, $this->queueUrl);

        return $this->queueUrl;
    }

    /**
     * Get a Topic ARN by name
     *
     * @return string
     */
    public function getTopicArn()
    {
        if (!empty($this->topicArn)) {
            return $this->topicArn;
        }

        $arnKey = $this->getNameWithPrefix() . '_topic_arn';
        if ($this->cache->contains($arnKey)) {
            return $this->topicArn = $this->cache->fetch($arnKey);
        }

        return $this->createTopic();
    }

    /**
     * Creates a SNS Topic and returns the ARN
     *
     * The create method for the SNS Topics is idempotent - if the topic already
     * exists, this method will return the Topic ARN of the existing Topic.
     *
     * @param string $name The name of the Queue to be used as a Topic Name
     *
     * @return string
     */
    public function createTopic()
    {
        $result = $this->snsClient->createTopic([
            'Name' => $this->getNameWithPrefix()
        ]);

        $this->topicArn = $result->get('TopicArn');

        $arnKey = $this->getNameWithPrefix() . '_topic_arn';
        $this->cache->save($arnKey, $this->queueUrl);

        return $this->topicArn;
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
        $result = $this->snsClient->listSubscriptionsByTopic([
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
    public function subscribeEndpoint($topicArn, $protocol, $endpoint)
    {
        // Check against the current Topic Subscriptions
        $subscriptions = $this->getTopicSubscriptions($topicArn);
        foreach ($subscriptions as $subscription) {
            if ($endpoint === $subscription['Endpoint']) {
                return $subscription['SubscriptionArn'];
            }
        }

        $result = $this->snsClient->subscribe([
            'TopicArn' => $topicArn,
            'Protocol' => $protocol,
            'Endpoint' => $endpoint
        ]);

        return $result->get('SubscriptionArn');
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
     * @return boolean
     */
    public function unsubscribeEndpoint($topicArn, $protocol, $endpoint)
    {
        // Check against the current Topic Subscriptions
        $subscriptions = $this->getTopicSubscriptions($topicArn);
        foreach ($subscriptions as $subscription) {
            if ($endpoint === $subscription['Endpoint']) {
                $result = $this->snsClient->unsubscribe([
                    'SubscriptionArn' => $subscription['SubscriptionArn']
                ]);

                return true;
            }
        }

        return false;
    }

    /**
     * Send Subscription Confirmation to SNS Topic
     *
     * SNS Topics require a confirmation to add or remove subscriptions. This
     * method will automatically confirm the subscription change.
     *
     * @param SubscriptionEvent $event The SNS Subscription Event
     */
    public function onSubscription(SubscriptionEvent $event)
    {
        $this->snsClient->confirmSubscription([
            'TopicArn'  => $event->getTopicArn(),
            'Token'     => $event->getToken()
        ]);
    }

    /**
     * Polls SQS Queue on Notificaiton from SNS
     *
     * Dispatches the `uecode_qpush.message_retrieved` event polling returns
     * SQS Messages
     *
     * @param NotificationEvent $event The SNS Notification Event
     */
    public function onNotify(NotificationEvent $event)
    {
        $messages = $this->pollQueue();
        foreach ($messages as $message) {
            $messageEvent   = new MessageEvent($queue, $message);

            $dispatcher = $event->getDispatcher();
            $dispatcher->dispatch(Events::Message($queue), $messageEvent);
        }
    }

    /**
     * Removes SQS Message from Queue after all other listeners have fired
     *
     * If an earlier listener has errored or stopped propigation, this method
     * will not fire and the Queued Message should become visible in SQS again.
     *
     * Stops Event Propagation after removing the Message
     *
     * @param MessageEvent $event The SQS Message Event
     */
    public function onMessage(MessageEvent $event)
    {
        $result = $this->sqsClient->deleteMessage([
            'QueueUrl'      => $this->getQueueUrl(),
            'ReceiptHandle' => $event->getReceiptHandle()
        ]);

        $event->stopPropagation();
    }
}

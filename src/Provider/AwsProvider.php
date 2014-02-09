<?php

namespace Uecode\Bundle\QPushBundle\Provider;

use Aws\Common\Aws;
use Aws\Sqs\SqsClient;

use Doctrine\Common\Cache\Cache;

use Uecode\Bundle\QPushBundle\Provider\QueueProvider;

use Uecode\Bundle\QPushBundle\Event\Events;
use Uecode\Bundle\QPushBundle\Event\MessageEvent;
use Uecode\Bundle\QPushBundle\Event\NotificationEvent;

use Uecode\Bundle\QPushBundle\Message\Message;

class AwsProvider extends QueueProvider
{
    /**
     * Aws SQS Client
     *
     * @var SqsClient
     */
    private $sqs;

    /**
     * Aws SQS Client
     *
     * @var SqsClient
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


    public function __construct($name, array $options, Cache $cache, Aws $aws)
    {
        $this->name     = $name;
        $this->options  = $options;
        $this->cache    = $cache;
        $this->sqs      = $aws->get('Sqs');
        $this->sns      = $aws->get('Sns');
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
     * @return bool
     */
    public function destroy()
    {
        // Delete the SQS Queue
        if ($this->queueExists()) {
            $this->sqs->deleteQueue([
                'QueueUrl' => $this->queueUrl
            ]);

            $key = $this->getNameWithPrefix() . '_url';
            $this->cache->delete($key);
        }

        // Delete the SNS Topic
        if ($this->topicExists()) {
            $this->sns->deleteTopic([
                'TopicArn' => $this->topicArn
            ]);

            $key = $this->getNameWithPrefix() . '_arn';
            $this->cache->delete($key);
        }

        return true;
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
    public function publish(array $message)
    {
        // ensures that the SQS Queue and SNS Topic exist
        if(!$this->queueExists()) {
            $this->create();
        }

        if ($this->options['push_notifications'] && $this->topicExists()) {
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

            return $result->get('MessageId');
        }

        $result = $this->sqs->sendMessage([
            'QueueUrl'      => $this->queueUrl,
            'MessageBody'   => json_encode($message),
            'DelaySeconds'  => $this->options['message_delay'] 
        ]);

        return $result->get('MessageId');
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
    public function receive()
    {
        if (!$this->queueExists()) {
            $this->create();
        }

        $result = $this->sqs->receiveMessage([
            'QueueUrl'              => $this->queueUrl,
            'MaxNumberOfMessages'   => $this->options['messages_to_receive'],
            'WaitTimeSeconds'       => $this->options['receive_wait_time']
        ]);

        $messages = $result->get('Messages') ?: [];

        // Convert to Message Class
        foreach($messages as &$message) {
            $id = $message['MessageId'];
            $metadata = [
                'ReceiptHandle' => $message['ReceiptHandle'],
                'MD5OfBody'     => $message['MD5OfBody']
            ];

            // When using SNS, the SQS Body is the entire SNS Message
            if(is_array($body = json_decode($message['Body'], true))) {
                $body = json_decode($body['Message'], true);
            }

            $message = new Message($id, $body, $metadata);
        }

        return $messages;
    }

    /**
     * @return bool
     */
    public function delete($id)
    {
        if (!$this->queueExists()) {
            return false;
        }

        $result = $this->sqs->deleteMessage([
            'QueueUrl'      => $this->queueUrl,
            'ReceiptHandle' => $message
        ]);

        return true;
    }

    /**
     * Return the Queue Url
     *
     * This method relies on in-memory cache and the Cache provider
     * to reduce the need to needlessly call the create method on an existing
     * Queue.
     *
     * @return string
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
            'QueueName' => $this->getNameWithPrefix()
        ]);

        $this->queueUrl = $result->get('QueueUrl');

        $key = $this->getNameWithPrefix() . '_url';
        $this->cache->save($key, $this->queueUrl);

        if ($this->options['push_notifications']) {

            $policy = $this->createSqsPolicy();

            $this->sqs->setQueueAttributes([
                'QueueUrl'      => $this->queueUrl,
                'Attributes'    => [
                    'Policy'                        => $policy,
                    'VisibilityTimeout'             => $this->options['message_timeout'],
                    'MessageRentionPeriod'          => $this->options['message_expiration'], 
                    'ReceiveMessageWaitTimeSeconds' => $this->options['receive_wait_time']
                ]
            ]);
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
     * @return string
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

        return false;
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
        if (!$this->options['push_notifications']) {
            return false;
        }

        $result = $this->sns->createTopic([
            'Name' => $this->getNameWithPrefix()
        ]);

        $this->topicArn = $result->get('TopicArn');

        $key = $this->getNameWithPrefix() . '_arn';
        $this->cache->save($key, $this->topicArn);
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
    public function unsubscribeFromTopic($topicArn, $protocol, $endpoint)
    {
        // Check against the current Topic Subscriptions
        $subscriptions = $this->getTopicSubscriptions($topicArn);
        foreach ($subscriptions as $subscription) {
            if ($endpoint === $subscription['Endpoint']) {
                $result = $this->sns->unsubscribe([
                    'SubscriptionArn' => $subscription['SubscriptionArn']
                ]);

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
     */
    public function onNotification(NotificationEvent $event)
    {
        if (NotificationEvent::TYPE_SUBSCRIPTION == $event->getType())
        {
            $topicArn   = $event->getNotification()->getMetadata()->get('TopicArn');
            $token      = $event->getNotification()->getMetadata()->get('Token');

            $this->sns->confirmSubscription([
                'TopicArn'  => $topicArn,
                'Token'     => $token
            ]);

            return;
        }

        $messages = $this->receive();
        foreach ($messages as $message) {
            $messageEvent = new MessageEvent($this->name, $message);
            $event->getDispatcher()->dispatch(Events::Message($this->name), $messageEvent);
        }
    }

    /**
     * Removes the message from queue after all other listeners have fired
     *
     * If an earlier listener has errored or stopped propigation, this method
     * will not fire and the Queued Message should become visible in queue again.
     *
     * Stops Event Propagation after removing the Message
     *
     * @param MessageEvent $event The SQS Message Event
     */
    public function onMessage(MessageEvent $event)
    {
        $receiptHandle = $event
            ->getMessage()
            ->getMetadata()
            ->get('ReceiptHandle');

        $this->delete($receiptHandle);

        $event->stopPropagation();
    }
}

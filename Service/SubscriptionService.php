<?php

namespace Uecode\Bundle\QPushBundle\Service;

use Uecode\Bundle\QPushBundle\EventListener\SubscriptionListener;

use Aws\Sns\SnsClient;

/**
 * Class SubscriptionService
 */
class SubscriptionService implements SubscriptionListener
{
    /**
     * AWS SNS Client
     *
     * @var Aws/Sns/SnsClient
     */
    private $client;

    /**
     * Constructor.
     *
     * @param SnsClient $client An AWS SNS Client
     */
    public function __construct(SnsClient $client)
    {
        $this->client = $client;
    }

    /**
     * Send Subscription Confirmation to SNS Topic
     *
     * SNS Topics require a confirmation to add or remove subscriptions. This
     * method will automatically confirm the subscription change.
     *
     * @param SubscriptionEvent $event The SNS Subscription Event
     */
    public function onSubscriptionChange(SubscriptionEvent $event)
    {
        $params = [
            'TopicArn'  => $event->getTopicArn(),
            'Token'     => $event->getToken()
        ];

        $client->confirmSubscription($params);
    }
}

The AWS Provider
==================

The AWS Provider uses SQS & SNS to create a Push Queue model.  SNS is optional with
this provider and its possible to use just SQS by utilizing the provided Console
Command to poll the queue.

###Configuration

This provider relies on the [AWS SDK for PHP](https://github.com/aws/aws-sdk-php), which
needs to be required in your `composer.json` file.

From there, the rest of the configuration is simple. You need to provide your
credentials in your configuration.

```yaml
#app/config.yml

uecode_qpush:
    cache_service: my_cache_service
    providers:
        aws:
            key:
            secret:
            region:
    queues:
        my_queue_name:
            provider: aws
            options:
                push_notifications: true
                subscribers:
                    - { endpoint: http://example.com/qpush, protocol: http }
```

###Using SNS

If you set `push_notifications` to `true` in your queue config, this provider
will automatically create the SNS Topic, subscribe your SQS queue to it, as well
as loop over your list of `subscribers`, adding them to your Topic.

This provider automatically handles Subscription Confirmations sent from SNS, as
long as the HTTP endpoint you've listed is externally accessible.

####SNS in Development

It is recommended to use your `config_dev.yml` file to disable the
`push_notifications` settings on your queues. Then use the `qpush:poll:queue`
Console Command to receive messages from SQS.

It is also possible to use [ngrok](https://ngrok.com/) to allow your development
environment to be reachable by SNS.  

You would need to update your `config_dev.yml` configuration to use the `ngrok` url on 
your subscribers.

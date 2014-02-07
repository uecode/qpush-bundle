AWS Queue Provider
==================

The AWS Provider uses SQS & SNS to create a Pub/Sub model.  SNS is optional with
this provider and its possible to use just SQS by utilizing a provided Console
Command to poll the queue.

###Configuration

This provider relies on the [AWS SDK for PHP](https://github.com/aws/aws-sdk-php) and
it is recommened to use the [Uecode Amazon Bundle](https://github.com/uecode/amazon-bundle)
to make semantic configuration easy.

To configure the use of this provider, you must have the `Aws\Common\Aws` service locator
configured as a service in the container.

Using the `Uecode Amazon Bundle`, that would look like this:

```yaml
uecode_amazon:
    accounts:
        main:
            key: somekey
            secret: somesecret
```

This would provide you with a container service id `uecode_amazon.instance.main`.

From there, the rest of the configuration is simple.

```yaml
#app/config.yml

uecode_qpush:
	cache_service: my_cache_service
    providers:
    	aws:
    		provider_service: uecode_amazon.instance.main
    queues:
        my_queue_name:
        	provider: aws
        	options:
            	push_notifications: true
            	subscribers:
                	- { endpoint: http://example.com, protocol: http }
```

###Using SNS

If you set `push_notifications` to `true` in your queue config, this provider
will automatically create the SNS Topic and subscribe your SQS queue, as well
as loop over your list of `subscribers`, adding them to your Topic.

This provider automatically handles Subscription Confirmations sent from SNS, as
long as the HTTP endpoint you've listed is externally accessible.

####SNS in Development

It is recommended to use your `config_dev.yml` file to disable the
`push_notifications` settings on your queues. Then use the `qpush:poll:queue`
Console Command to receive messages from SQS.

It is also possible to use [ngrok](https://ngrok.com/) to allow your development
environment to be reachable by SNS.  You would need to update your `config_dev.yml`
configuration to use the `ngrok` url.

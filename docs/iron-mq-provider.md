The IronMQ Provider
==================

The IronMQ Provider uses the idea of Push Queues to create a Pub/Sub model. 
Using a Push Queue is optional with this provider and its possible to use simple
Pull queues by utilizing a provided Console Command to poll the queue.

###Configuration

This provider relies on the [Iron MQ PHP SDK](https://github.com/iron-io/iron_mq_php)
and needs to have the library included in your `composer.json` file.

Configuring the provider is very easy. It requires that you have already created
an account and Project. [Iron.io](http://www.iron.io/) provides free accounts for
Development.

Just include your OAuth `token` and `project_id` in the configuration and set your
queue to use the `ironmq` provider.

```yaml
#app/config.yml

uecode_qpush:
    cache_service: my_cache_service
    providers:
        ironmq:
            token:
            project_id:
    queues:
        my_queue_name:
            provider: ironmq
            options:
                push_notifications: true
                subscribers:
                    - { endpoint: http://example.com, protocol: http }
```

###Using Push Queues

If you set `push_notifications` to `true` in your queue config, this provider
will automatically create your Queue as a Push Queue loop over your list of `subscribers`,
adding them to your Queue.

This provider only supports `http` and `https` subscribers. This provider also uses the
`multicast` setting for its Push Queues, meaning that all `subscribers` are notified of
new messages.

####Push Queues in Development

It is recommended to use your `config_dev.yml` file to disable the
`push_notifications` settings on your queues. This will make the queue a simple 
Pull queue. You can then use the `uecode:qpush:receive` Console Command to receive 
messages from your Queue.

If you need to test the Push Queue functionality from a local stack or internal
machine, it possible to use [ngrok](https://ngrok.com/) to allow your development
environment to be reachable by IronMQ. 

You would need to update your `config_dev.yml` configuration to use the `ngrok` url.

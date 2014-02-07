QPush Bundle
=======================

###Overview

The QPush Bundle relies on a Pub/Sub model of Worker Queues to provide asynchronous
processing in your Symfony application.  This allows you to distribute processing to
multiple consumers and create and chain services by binding to simple events.

###Installation

The bundle should be installed through composer.

#####Add the bundle to your `composer.json` file

```json
"require": {
    "uecode/qpush-bundle": "~0.2",
}
```

#####Update AppKernel.php of your Symfony Application

Add the `UecodeQPushBundle` to your kernel bootstrap sequence, in the `$bundles` array.

```php
public function registerBundles()
{
    $bundles = array(
        // ...
        new Uecode\Bundle\QPushBundle\UecodeQPushBundle(),
    );

    return $bundles;
}
```

###Configure the Bundle

The bundle allows you to specify different Worker Queue providers - however, 
Amazon AWS is the only one currently supported. We are actively looking to add
more and would be more than happy to accept contributions.

####Providers

This bundle allows you to configure and use multiple supported providers with in the same 
application.  Each queue that you create is attached to one of you registered providers.

Providers may require a Service or Client injected into them to provide basic
interaction with that Worker Queue.  As an example, the `aws` provider requires the
`Aws\Common\Aws` client.

For specific instructions on how to configure each provider, please view their configuration
documents below:

 - [AWS Queue Provider](/docs/aws-queue-provider.md)

####Caching

Some providers can leverage a caching layer to limit the amount of calls to the Worker Queue
for basic lookup functionality for things like the Queue ARN, etc.

By default the library will attempt to use file cache, however you can pass your
own cache service, as long as its an instance of `Doctrine\Common\Cache\Cache`.

The configuration parameter `cache_service` expects the container service id of a registered
Cache service.

######Example:
```yaml
#app/config.yml

services:
    my_cache_service:
    	class: My\Caching\CacheService

uecode_qpush:
    cache_service: my_cache_service
```

####Full Configuration:

A full configuration might look like the follow:

######Example

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

###Usage

Once configured, you can create messages and publish them to the queue.  You may also
create services that will automatically be fired as messages are pushed to your application.

For your convenience, a custom `QueueProvider` service will be created and registered in the Container for
each of your defined Queues. The service id will be in the format of `uecode_qpush.{queue name}`.

####Publishing a Message to your Worker Queue

Publishing messages is simple: fetch your `QueueProvider` service from the container and
call the `publish` method, which accepts an array.

######Example

```php
#src/My/Bundle/ExampleBundle/Controller/MyController.php

public function publishAction()
{
    $message = [ 
        'messages should be an array'.
        'they can be flat arrays' => [
            'or multidimensional'
        ]
    ];
    
    $this->get('uecode_qpush.my_queue_name')->publish($message);
}

```

####Working with Messages from your Queue

Messages are ether automatically received by your application from Subscriber callbacks,
or can be picked up by Cron through an included command if you are not using AWS SNS.

Once a message is received from your Worker Queue, a `MessageEvent` is dispatched which
can be handled by your services.

Services to be called on events must be tagged with the `name` as `uecode_qpush.event_listener`, the
`event` to listen for, the `method` to call on, and optionally a `priority` between `1` and `100`.

Each `event` fired by the Qpush Bundle is prefixed with the name of your `queue`, ex: `my_queue_name.message`.
This allows you to assign which services should be used based on the `queue`.

You may also have multiple tags on a single service, so that one service can handle multiple `queue`'s events.

######Example
```yaml
    services:
    	my_example_service:
    		class: My\Example\ExampleService
    		tags:
    			- { name: uecode_qpush.event_listener, event: my_queue_name.message, method: onMessage }
```

The `method` used in the tag must be publicly available in your service and take one argument,
an instance of `Uecode\Bundle\QPushBundle\Event\MessageEvent`.

######Example
```php
#src/My/Bundle/ExampleBundle/Service/MyService.php

use Uecode\Bundle\QpushBundle\Event\MessageEvent;

// ...

public function onMessage(MessageEvent $event)
{
    $queueName    = $event->getQueueName();
    $message    = $event->getMessage();
    $metadata    = $event->getMetadata();
    
    // Process ...
}
```

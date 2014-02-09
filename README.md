QPush Bundle
=======================

##Overview

The QPush Bundle relies on a Pub/Sub model of Message Queues to provide asynchronous
processing in your Symfony application. This allows you to distribute processing to
multiple consumers and create and chain services by binding to simple events.

 * [Installation](#installation)
 * [Configuring](#configure-the-bundle)
    - [Providers](#providers)
    - [Caching](#caching)
    - [Queue Options](#queue-options)
    - [Example Configuration](#example-configuration)
 * [Usage](#usage)
    - [Publishing Messages to your Message Queue](#publishing-messages-to-your-message-queue)
    - [Working with Messages from your Message Queue](#working-with-messages-from-your-message-queue)
        - [Message Events](#message-events)
        - [Tagging Your Services](#tagging-your-services)
        - [Cleaning up the Queue](#cleaning-up-the-queue)
 * [Console Commands](#console-commands)
        

##Installation

The bundle should be installed through composer.

####Add the bundle to your `composer.json` file

```json
"require": {
    "uecode/qpush-bundle": "~0.3",
}
```

####Update AppKernel.php of your Symfony Application

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

##Configure the Bundle

The bundle allows you to specify different Message Queue providers - however, 
Amazon AWS and IronMQ are the only ones currently supported. We are actively looking to add
more and would be more than happy to accept contributions.

###Providers

This bundle allows you to configure and use multiple supported providers with in the same 
application. Each queue that you create is attached to one of your registered providers
and can have its own configuration options.

Providers may have their own dependencies that should be added to your `composer.json` file.

For specific instructions on how to configure each provider, please view their configuration
documents below:

 - [AWS Provider](/docs/aws-provider.md)
 - [IronMQ Provider](/docs/iron-mq-provider.md)

###Caching

Providers can leverage a caching layer to limit the amount of calls to the Message Queue
for basic lookup functionality for things like the Queue ARN, etc.

By default the library will attempt to use file cache, however you can pass your
own cache service, as long as its an instance of `Doctrine\Common\Cache\Cache`.

The configuration parameter `cache_service` expects the container service id of a registered
Cache service. See below.

######Example:
```yaml
#app/config.yml

services:
    my_cache_service:
    	class: My\Caching\CacheService

uecode_qpush:
    cache_service: my_cache_service
```

###Queue Options

Each queue can their own options that determine how messages are published or receieved. 
The options and their descriptions are listed below.

Option | Description | Default
------ | ----------- | -------
`push_notifications` | Whether or not to POST notifications to subscribers of a Queue | `false`
`message_delay` | Time in seconds of how long before a new Message is available to be read in a Queue | `0`
`message_timeout` | How long in secons a worker has to respond or delete a Message before its again made available | `30`
`message_expiration` | How long in seconds the Messages may remain in the queue before being deleted | `604800`
`messages_to_receive` | How many messages should be received when polling the queue | `1`
`receive_wait_time` | If supported, how long in seconds to leave the polling request open - for long polling operations | `3`
`subscribers` | An array of Subscribers, containing an `endpoint` and `protocol` - used when `push_notificaitons` is `true` | `empty`

###Example Configuration:

To see a an example configuration file that has all the available options, please
take a look [here](src/Resources/config/config.yml).

However, a working configuration would look like the following:

######Example

```yaml
#app/config.yml

uecode_qpush:
    cache_service: my_cache_service
    providers:
        aws:
            key:    <aws api key>
            secret: <aws secret>
            region: us-east-1
    queues:
        my_queue_name:
            provider: aws
            options:
                push_notifications: true
                subscribers:
                    - { endpoint: http://example.com, protocol: http }
```

##Usage

Once configured, you can create messages and publish them to the queue. You may also
create services that will automatically be fired as messages are pushed to your application.

For your convenience, a custom `Provider` service will be created and registered 
in the Container for each of your defined Queues. The container service id will be 
in the format of `uecode_qpush.{your queue name}`.

###Publishing a Message to your Message Queue

Publishing messages is simple - fetch your `Provider` service from the container and
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

###Working with Messages from your Queue

Messages are either automatically received by your application from Subscriber 
callbacks (setting `push_notification` to true), or can be picked up by Cron jobs
through an included command if you are not using a Message Queue provider that supports
Push notifications.

####MessageEvents

Once a message is received via POST from your Message Queue, a `MessageEvent` is dispatched
which can be handled by your services. Each `MessageEvent` contains the name of the queue
and a `Uecode\Bundle\QPushBundle\Message\Message` object, accessible through getters.

######Example
```php
    $queue_name = $event->getQueueName();
    $message    = $event->getMessage();
```

The `Message` objects contain an `id`, the message `body`, and `metadata`. These properties
are also accessible through simple getters. 

The message `body` is an array matching your original message. The `metadata` property is an
`ArrayCollection` of varying fields sent with your message from your Queue Provider.

######Example
```php
    $id         = $event->getMessage()->getId();
    $body       = $event->getMessage()->getBody();
    $metadata   = $event->getMessage()->getMetadata();
```

####Tagging Your Services

For your Services to be called on QPush events, they  must be tagged with the name
`uecode_qpush.event_listener`. A complete tag is made up of the following properties:

Tag Property | Example | Description
------------ | ------- | -----------
`name` | `uecode_qpush.event_listener` | The Qpush Event Listener Tag
`event` | `{queue name}.message_received` | The `message_received` event, prefixed with the Queue name
`method` | `onMessageReceived` | A publicly accessbile method on your service
`priority` | `100` | Priority, `1`-`100` to control order of services. Higher priorities are called earlier

The `priority` is useful to chain services and ensure they fire in a certain order - 
the higher priorities fire earlier.

Each event fired by the Qpush Bundle is prefixed with the name of your queue, 
ex: `my_queue_name.message_received`. 

This allows you to assign services to fire only on certain queues should be used based on the `queue`.
However, you may also have multiple tags on a single service, so that one service can handle
events from multiple queues.

######Example
```yaml
    services:
    	my_example_service:
    		class: My\Example\ExampleService
    		tags:
    			- { name: uecode_qpush.event_listener, event: my_queue_name.message_received, method: onMessageReceived }
```

The method listed in the tag must be publicly available in your service and should
take a single argument, an instance of `Uecode\Bundle\QPushBundle\Event\MessageEvent`.

######Example
```php
#src/My/Bundle/ExampleBundle/Service/MyService.php

use Uecode\Bundle\QpushBundle\Event\MessageEvent;

// ...

public function onMessageReceived(MessageEvent $event)
{
    $queueName    = $event->getQueueName();
    $message    = $event->getMessage();
    $metadata    = $event->Message()->getMetadata();
    
    // Process ...
}
```

####Cleaning Up the Queue

Once all other Event Listeners have been invoked on a `MessageEvent`, the QPush Bundle
will automatically attempt to remove the Message from your Queue.

If an error or exception is thrown, or event propagation is stopped earlier in the chain,
the Message will not be removed automatically and may be picked up by other workers.

If you would like to remove the message in your service, you can do so by calling the `delete`
method on your provider and passing it the message `id`.  However, you must also stop
the event propagation to avoid other services (including the Provider service) from firing on that
`MessageEvent`.

######Example
```php
#src/My/Bundle/ExampleBundle/Service/MyService.php

use Uecode\Bundle\QpushBundle\Event\MessageEvent;

// ...

public function onMessageReceived(MessageEvent $event)
{
    $id = $event->getMessage()->getId();
    // Removes the message from the queue
    $awsProvider->delete($id);

    // Stops the event from propagating
    $event->stopPropagation();
}
```



##Console Commands

This bundle includes some Console Commands which can be used to for building and polling your queues
as well as sending simple messages.

Command | Description
------- | -----------
`uecode:qpush:build`   | Builds the queues and warms cache. Can take an optional queue name as an argument to build a single queue.
`uecode:qpush:destroy` | Destroys the queues and clears cache. Can take an optional queue name as an argument to destroy a single queue.
`uecode:qpush:receive` | Polls messages from the queues. Can take an optional queue name as an argument to poll from a single queue.
`uecode:qpush:publish` | Sends a message to a queue. Takes a queue name and a string message as required arguments.

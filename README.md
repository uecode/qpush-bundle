QPush - Symfony2 Push Queue Bundle
==================================

[![Build Status](https://img.shields.io/travis/uecode/qpush-bundle/master.svg?style=flat-square)](https://travis-ci.org/uecode/qpush-bundle)
[![Quality Score](https://img.shields.io/scrutinizer/g/uecode/qpush-bundle.svg?style=flat-square)](https://scrutinizer-ci.com/g/uecode/qpush-bundle/)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/uecode/qpush-bundle.svg?style=flat-square)](https://scrutinizer-ci.com/g/uecode/qpush-bundle/)
[![Total Downloads](http://img.shields.io/packagist/dt/uecode/qpush-bundle.svg?style=flat-square)](https://packagist.org/packages/uecode/qpush-bundle)

##Overview
This bundle allows you to easily consume messages from Push Queues by simply
tagging your services and relying on Symfony's event dispatcher - without
needing to run a daemon or background process to continuously poll your queue.

**Full Documentation:** [qpush-bundle.readthedocs.org](http://qpush-bundle.rtfd.org)

##Installation

The bundle should be installed through composer.

####Add the bundle to your composer.json file

```json
{
    "require": {
        "uecode/qpush-bundle": "~2.2.0",
    }
}
```

####Update AppKernel.php of your Symfony Application

Add the `UecodeQPushBundle` to your kernel bootstrap sequence, in the `$bundles`
array.

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

##Basic Configuration:

Here is a basic configuration that would create a push queue called
`my_queue_name` using AWS or IronMQ. You can read about the supported providers
and provider options in the [full documentation](http://qpush-bundle.rtfd.org).

######Example

```yaml
#app/config.yml

uecode_qpush:
    providers:
        ironmq:
            token:      YOUR_IRON_MQ_TOKEN_HERE
            project_id: YOUR_IRON_MQ_PROJECT_ID_HERE
        aws:
            key:    YOUR_AWS_KEY_HERE
            secret: YOUR_AWS_SECRET_HERE
            region: YOUR_AWS_REGION_HERE
    queues:
        my_queue_key:
            provider: ironmq #or aws
            options:
                queue_name: my_queue_name #optional. the queue name used on the provider
                push_notifications: true
                subscribers:
                    - { endpoint: http://example.com/qpush, protocol: http }
```

You may exclude aws key and secret to default to IAM role on the EC2 machine.

##Publishing messages to your Queue

Publishing messages is simple - fetch the registered Provider service from the
container and call the `publish` method on the respective queue.

This bundle stores your messages as a json object and the publish method expects
an array, typically associative.

######Example

```php
// src/My/Bundle/ExampleBundle/Controller/MyController.php

public function publishAction()
{
    $message = ['foo' => 'bar'];

    // fetch your provider service from the container
    $this->get('uecode_qpush')->get('my_queue_key')->publish($message);

    // you can also fetch it directly
    $this->get('uecode_qpush.my_queue_key')->publish($message);
}

```

##Working with messages from your Queue

When a message hits your application, this bundle will dispatch a `MessageEvent`
which can be handled by your services. You need to tag your services to handle
these events.

######Example
```yaml
services:
    my_example_service:
    	class: My\Bundle\ExampleBundle\Service\ExampleService
    	tags:
    		- { name: uecode_qpush.event_listener, event: my_queue_key.message_received, method: onMessageReceived }
```

######Example
```php
// src/My/Bundle/ExampleBundle/Service/ExampleService.php

use Uecode\Bundle\QPushBundle\Event\MessageEvent;

public function onMessageReceived(MessageEvent $event)
{
    $queue_name = $event->getQueueName();
    $message    = $event->getMessage();

    // do some processing
}
```

The `Message` objects contain the provider specific message id, a message body,
and a collection of provider specific metadata.

These properties are accessible through simple getters from the message object.

######Example
```php
// src/My/Bundle/ExampleBundle/Service/ExampleService.php

use Uecode\Bundle\QPushBundle\Event\MessageEvent;
use Uecode\Bundle\QPushBundle\Message\Message;

public function onMessageReceived(MessageEvent $event)
{
    $id         = $event->getMessage()->getId();
    $body       = $event->getMessage()->getBody();
    $metadata   = $event->getMessage()->getMetadata();

    // do some processing
}
```

###Cleaning up the Queue

Once all other Event Listeners have been invoked on a `MessageEvent`, the Bundle
will automatically attempt to remove the Message from your Queue for you.


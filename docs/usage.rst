Usage
=====

Once configured, you can create messages and publish them to the queue. You may also
create services that will automatically be fired as messages are pushed to your application.

For your convenience, a custom ``Provider`` service will be created and registered
in the Container for each of your defined Queues. The container queue service id will be
in the format of ``uecode_qpush.{your queue name}``.

Publishing messages to your Queue
---------------------------------

Publishing messages is simple - fetch your ``Provider`` service from the container and
call the ``publish`` method on the respective queue, which accepts an array.

.. code-block:: php

    #src/My/Bundle/ExampleBundle/Controller/MyController.php

    public function publishAction()
    {
        $message = [
            'messages should be an array',
            'they can be flat arrays' => [
                'or multidimensional'
            ]
        ];

        $this->get('uecode_qpush.my_queue_name')->publish($message);
    }

Working with messages from your Queue
-------------------------------------

Messages are either automatically received by your application and events dispatched
(setting ``push_notification`` to ``true``), or can be picked up by Cron jobs through an included
command if you are not using a Message Queue provider that supports Push notifications.

When the notifications or messages are Pushed to your application, the QPush Bundle automatically
catches the request and dispatches an event which can be easily hooked into.

MessageEvents
^^^^^^^^^^^^^

Once a message is received via POST from your Message Queue, a ``MessageEvent`` is dispatched
which can be handled by your services. Each ``MessageEvent`` contains the name of the queue
and a ``Uecode\Bundle\QPushBundle\Message\Message`` object, accessible through getters.

.. code-block:: php

    #src/My/Bundle/ExampleBundle/Service/ExampleService.php

    use Uecode\Bundle\QPushBundle\Event\MessageEvent

    public function onMessageReceived(MessageEvent $event)
    {
        $queue_name = $event->getQueueName();
        $message    = $event->getMessage();
    }

The ``Message`` objects contain the provider specific message id, a message body,
and a collection of provider specific metadata.

These properties are accessible through simple getters.

The message ``body`` is an array matching your original message. The ``metadata`` property is an
``ArrayCollection`` of varying fields sent with your message from your Queue Provider.

.. code-block:: php

    #src/My/Bundle/ExampleBundle/Service/ExampleService.php

    use Uecode\Bundle\QPushBundle\Event\MessageEvent;
    use Uecode\Bundle\QPushBundle\Message\Message;

    public function onMessageReceived(MessageEvent $event)
    {
        $id         = $event->getMessage()->getId();
        $body       = $event->getMessage()->getBody();
        $metadata   = $event->getMessage()->getMetadata();

        // do some processing
    }

Tagging Your Services
^^^^^^^^^^^^^^^^^^^^^

For your Services to be called on QPush events, they must be tagged with the name
``uecode_qpush.event_listener``. A complete tag is made up of the following properties:

============    =================================       ==========================================================================================
Tag Property    Example                                 Description
============    =================================       ==========================================================================================
``name``        ``uecode_qpush.event_listener``         The Qpush Event Listener Tag
``event``       ``{queue name}.message_received``       The `message_received` event, prefixed with the Queue name
``method``      ``onMessageReceived``                   A publicly accessible method on your service
``priority``    ``100``                                 Priority, ``1``-``100`` to control order of services. Higher priorities are called earlier
============    =================================       ==========================================================================================

The ``priority`` is useful to chain services, ensuring that they fire in a certain order - the higher priorities fire earlier.

Each event fired by the Qpush Bundle is prefixed with the name of your queue, ex: ``my_queue_name.message_received``.

This allows you to assign services to fire only on certain queues, based on the queue name.
However, you may also have multiple tags on a single service, so that one service can handle
events from multiple queues.

.. code-block:: yaml

    services:
        my_example_service:
        class: My\Example\ExampleService
        tags:
            - { name: uecode_qpush.event_listener, event: my_queue_name.message_received, method: onMessageReceived }

The method listed in the tag must be publicly available in your service and should
take a single argument, an instance of ``Uecode\Bundle\QPushBundle\Event\MessageEvent``.

.. code-block:: php

    #src/My/Bundle/ExampleBundle/Service/MyService.php

    use Uecode\Bundle\QPushBundle\Event\MessageEvent;

    // ...

    public function onMessageReceived(MessageEvent $event)
    {
        $queueName  = $event->getQueueName();
        $message    = $event->getMessage();
        $metadata   = $message()->getMetadata();

        // Process ...
    }

Cleaning Up the Queue
---------------------

Once all other Event Listeners have been invoked on a ``MessageEvent``, the QPush Bundle
will automatically attempt to remove the Message from your Queue for you.

If an error or exception is thrown, or event propagation is stopped earlier in the chain,
the Message will not be removed automatically and may be picked up by other workers.

If you would like to remove the message inside your service, you can do so by calling the ``delete``
method on your provider and passing it the message ``id``.  However, you must also stop
the event propagation to avoid other services (including the Provider service) from firing on that
``MessageEvent``.

.. code-block:: php

    #src/My/Bundle/ExampleBundle/Service/MyService.php

    use Uecode\Bundle\QPushBundle\Event\MessageEvent;

    // ...

    public function onMessageReceived(MessageEvent $event)
    {
        $id = $event->getMessage()->getId();
        // Removes the message from the queue
        $awsProvider->delete($id);

        // Stops the event from propagating
        $event->stopPropagation();
    }

Push Queues in Development
--------------------------

It is recommended to use your ``config_dev.yml`` file to disable the
``push_notifications`` settings on your queues. This will make the queue a simple
Pull queue. You can then use the ``uecode:qpush:receive`` Console Command to receive
messages from your Queue.

If you need to test the Push Queue functionality from a local stack or internal
machine, it's possible to use `ngrok <https://ngrok.com/>`_ to tunnel to your development
environment, so its reachable by your Queue Provider.

You would need to update your `config_dev.yml` configuration to use the `ngrok` url for
your subscriber(s).

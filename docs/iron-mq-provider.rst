IronMQ Provider
---------------

The IronMQ Provider uses its Push Queues to notify subscribers of new queued
messages without needing to continually poll the queue.

Using a Push Queue is optional with this provider and its possible to use simple
Pull queues by utilizing the provided Console Command (``uecode:qpush::receive``)
to poll the queue.

Configuration
^^^^^^^^^^^^^

This provider relies on the `Iron MQ <https://github.com/iron-io/iron_mq_php>`_ classes
and needs to have the library included in your ``composer.json`` file.

.. code-block:: js

    {
        require: {
            "iron-io/iron_mq": "^4.0"
        }
    }


Configuring the provider is very easy. It requires that you have already created
an account and have a project id.

`Iron.io <http://www.iron.io/>`_ provides free accounts for Development, which makes
testing and using this service extremely easy.

Just include your OAuth `token` and `project_id` in the configuration and set your
queue to use a provider using the `ironmq` driver.

.. code-block:: yaml

    #app/config.yml

    uecode_qpush:
        providers:
            my_provider:
                driver:     ironmq
                token:      YOUR_TOKEN_HERE
                project_id: YOUR_PROJECT_ID_HERE
                host:       YOUR_OPTIONAL_HOST_HERE
                port:       YOUR_OPTIONAL_PORT_HERE
                version_id: YOUR_OPTIONAL_VERSION_HERE
        queues:
            my_queue_name:
                provider: my_provider
                options:
                    push_notifications: true
                    subscribers:
                        - { endpoint: http://example.com/qpush, protocol: http }

IronMQ Push Queues
^^^^^^^^^^^^^^^^^^

If you set ``push_notifications`` to ``true`` in your queue config, this provider
will automatically create your Queue as a Push Queue and loop over your list of ``subscribers``,
adding them to your Queue.

This provider only supports ``http`` and ``https`` subscribers. This provider also uses the
``multicast`` setting for its Push Queues, meaning that all ``subscribers`` are notified of
the same new messages.

You can chose to have your IronMQ queues work as a Pull Queue by setting ``push_notifications`` to ``false``.
This would require you to use the ``uecode:qpush:receive`` Console Command to poll the queue.

Overriding Queue Options
^^^^^^^^^^^^^^^^^^^^^^^^

It's possible to override the default queue options that are set in your config file
when sending or receiving messages.

**Publishing**

The ``publish()`` method takes an array as a second argument. For the IronMQ
Provider you are able to change the options listed below per publish.

+--------------------------+-------------------------------------------------------------------------------------------+---------------+
| Option                   | Description                                                                               | Default Value |
+==========================+===========================================================================================+===============+
| ``message_delay``        | Time in seconds before a published Message is available to be read in a Queue             | ``0``         |
+--------------------------+-------------------------------------------------------------------------------------------+---------------+
| ``message_timeout``      | Time in seconds a worker has to delete a Message before it is available to other workers  | ``30``        |
+--------------------------+-------------------------------------------------------------------------------------------+---------------+
| ``message_expiration``   | Time in seconds that Messages may remain in the Queue before being removed                | ``604800``    |
+--------------------------+-------------------------------------------------------------------------------------------+---------------+

.. code-block:: php

    $message = ['foo' => 'bar'];

    // Optional config to override default options
    $options = [
        'message_delay'      => 1,
        'message_timeout'    => 1,
        'message_expiration' => 60
    ];

    $this->get('uecode_qpush.my_queue_name')->publish($message, $options);


**Receiving**

The ``receive()`` method takes an array as a second argument. For the AWS Provider
you are able to change the options listed below per attempt to receive messages.

+--------------------------+-------------------------------------------------------------------------------------------+---------------+
| Option                   | Description                                                                               | Default Value |
+==========================+===========================================================================================+===============+
| ``messages_to_receive``  | Maximum amount of messages that can be received when polling the queue                    | ``1``         |
+--------------------------+-------------------------------------------------------------------------------------------+---------------+
| ``message_timeout``      | Time in seconds a worker has to delete a Message before it is available to other workers  | ``30``        |
+--------------------------+-------------------------------------------------------------------------------------------+---------------+

.. code-block:: php

    // Optional config to override default options
    $options = [
        'messages_to_receive' => 3,
        'message_timeout'     => 10
    ];

    $messages = $this->get('uecode_qpush.my_queue_name')->receive($options);

    foreach ($messages as $message) {
        echo $message->getBody();
    }

AWS Provider
------------

The AWS Provider uses SQS & SNS to create a Push Queue model. SNS is optional with
this provider and its possible to use just SQS by utilizing the provided Console
Command (``uecode:qpush:receive``) to poll the queue.

Configuration
^^^^^^^^^^^^^

This provider relies on the `AWS SDK PHP v2 <https://github.com/aws/aws-sdk-php>`_ library, which
needs to be required in your ``composer.json`` file.

.. code-block:: js

    {
        require: {
            "aws/aws-sdk-php": : "2.*"
        }
    }

From there, the rest of the configuration is simple. You need to provide your
credentials in your configuration.

.. code-block:: yaml

    #app/config.yml

    uecode_qpush:
        providers:
            my_provider:
                driver: aws
                key:    <aws key>
                secret: <aws secret>
                region: us-east-1
        queues:
            my_queue_name:
                provider: my_provider
                options:
                    push_notifications: true
                    subscribers:
                        - { endpoint: http://example.com/qpush, protocol: http }

You may exclude the aws key and secret if you are using IAM role in EC2.

Using SNS
^^^^^^^^^

If you set ``push_notifications`` to ``true`` in your queue config, this provider
will automatically create the SNS Topic, subscribe your SQS queue to it, as well
as loop over your list of ``subscribers``, adding them to your Topic.

This provider automatically handles Subscription Confirmations sent from SNS, as
long as the HTTP endpoint you've listed is externally accessible and has the QPush Bundle
properly installed and configured.

Overriding Queue Options
^^^^^^^^^^^^^^^^^^^^^^^^

It's possible to override the default queue options that are set in your config file
when sending or receiving messages.

**Publishing**

The ``publish()`` method takes an array as a second argument. For the AWS Provider
you are able to change the options listed below per publish.

If you disable ``push_notifications`` for a message, it will skip using SNS and
only write the message to SQS.  You will need to manually poll the SQS queue to
fetch those messages.

+--------------------------+-------------------------------------------------------------------------------------------+---------------+
| Option                   | Description                                                                               | Default Value |
+==========================+===========================================================================================+===============+
| ``push_notifications``   | Whether or not to POST notifications to subscribers of a Queue                            | ``false``     |
+--------------------------+-------------------------------------------------------------------------------------------+---------------+
| ``message_delay``        | Time in seconds before a published Message is available to be read in a Queue             | ``0``         |
+--------------------------+-------------------------------------------------------------------------------------------+---------------+

.. code-block:: php

    $message = ['foo' => 'bar'];

    // Optional config to override default options
    $options = [
        'push_notifications' => 0,
        'message_delay'      => 1
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
| ``receive_wait_time``    | If supported, time in seconds to leave the polling request open - for long polling        | ``3``         |
+--------------------------+-------------------------------------------------------------------------------------------+---------------+

.. code-block:: php

    // Optional config to override default options
    $options = [
        'messages_to_receive' => 3,
        'receive_wait_time'   => 10
    ];

    $messages = $this->get('uecode_qpush.my_queue_name')->receive($options);

    foreach ($messages as $message) {
        echo $message->getBody();
    }

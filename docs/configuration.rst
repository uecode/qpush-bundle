Configure the Bundle
====================

The bundle allows you to specify different Message Queue providers - however,
Amazon AWS and IronMQ are the only ones currently supported. Blocking, synchronous queues
are also supported through the ``sync`` driver to aid development and debugging.

We are actively looking to add more and would be more than happy to accept contributions.

Providers
---------

This bundle allows you to configure and use multiple supported providers with in the same
application. Each queue that you create is attached to one of your registered providers
and can have its own configuration options.

Providers may have their own dependencies that should be added to your ``composer.json`` file.

For specific instructions on how to configure each provider, please view their documents.

.. toctree::
    :maxdepth: 2

    aws-provider
    iron-mq-provider
    sync-provider
    file-provider
    custom-provider

Caching
-------

Providers can leverage a caching layer to limit the amount of calls to the Message Queue
for basic lookup functionality - this is important for things like AWS's ARN values, etc.

By default the library will attempt to use file cache, however you can pass your
own cache service, as long as its an instance of ``Doctrine\Common\Cache\Cache``.

The configuration parameter ``cache_service`` expects the container service id of a registered
Cache service. See below.

.. code-block:: yaml

    #app/config.yml

    services:
        my_cache_service:
            class: My\Caching\CacheService

    uecode_qpush:
        cache_service: my_cache_service

**Note:** *Though the Queue Providers will attempt to create queues if they do not exist when publishing or receiving messages,
it is highly recommended that you run the included console command to build queues and warm cache from the CLI beforehand.*

Queue Options
-------------

Each queue can have their own options that determine how messages are published or received.
The options and their descriptions are listed below.

+--------------------------+-------------------------------------------------------------------------------------------+---------------+
| Option                   | Description                                                                               | Default Value |
+==========================+===========================================================================================+===============+
| ``queue_name``           | The name used to describe the queue on the Provider's side                                | ``null``      |
+--------------------------+-------------------------------------------------------------------------------------------+---------------+
| ``push_notifications``   | Whether or not to POST notifications to subscribers of a Queue                            | ``false``     |
+--------------------------+-------------------------------------------------------------------------------------------+---------------+
| ``notification_retries`` | How many attempts notifications are resent in case of errors - if supported               | ``3``         |
+--------------------------+-------------------------------------------------------------------------------------------+---------------+
| ``message_delay``        | Time in seconds before a published Message is available to be read in a Queue             | ``0``         |
+--------------------------+-------------------------------------------------------------------------------------------+---------------+
| ``message_timeout``      | Time in seconds a worker has to delete a Message before it is available to other workers  | ``30``        |
+--------------------------+-------------------------------------------------------------------------------------------+---------------+
| ``message_expiration``   | Time in seconds that Messages may remain in the Queue before being removed                | ``604800``    |
+--------------------------+-------------------------------------------------------------------------------------------+---------------+
| ``messages_to_receive``  | Maximum amount of messages that can be received when polling the queue                    | ``1``         |
+--------------------------+-------------------------------------------------------------------------------------------+---------------+
| ``receive_wait_time``    | If supported, time in seconds to leave the polling request open - for long polling        | ``3``         |
+--------------------------+-------------------------------------------------------------------------------------------+---------------+
| ``subscribers``          | An array of Subscribers, containing an ``endpoint`` and ``protocol``                      | ``empty``     |
+--------------------------+-------------------------------------------------------------------------------------------+---------------+

Symfony Application as a Subscriber
-----------------------------------

The QPush Bundle uses a Request Listener which will capture and dispatch notifications from your queue providers for you. The specific route you use does not matter.

In most cases, it is recommended to just list the host or domain for your Symfony application as the ``endpoint`` of your subscriber.  You do not need to create a new action for QPush to receive messages.

Logging with Monolog
--------------------

By default, logging is enabled in the Qpush Bundle and uses Monolog, configured
via the MonologBundle. You can toggle the logging behavior by setting
``logging_enabled`` to ``false``.

Logs will output to your default Symfony environment logs using the 'qpush' channel.

Example Configuration
---------------------

A working configuration would look like the following

.. code-block:: yaml

    uecode_qpush:
        cache_service: null
        logging_enabled: true
        providers:
            aws:
                driver: aws #optional for providers named 'aws' or 'ironmq'
                key: YOUR_AWS_KEY_HERE
                secret: YOUR_AWS_SECRET_HERE
                region: YOUR_AWS_REGION_HERE
            another_aws_provider:
                driver: aws #required for named providers
                key: YOUR_AWS_KEY_HERE
                secret: YOUR_AWS_SECRET_HERE
                region: YOUR_AWS_REGION_HERE
            ironmq:
                driver: aws #optional for providers named 'aws' or 'ironmq'
                token: YOUR_IRONMQ_TOKEN_HERE
                project_id: YOUR_IRONMQ_PROJECT_ID_HERE
            in_band:
                driver: sync
            custom_provider:
                driver: custom
                service: YOUR_CUSTOM_SERVICE_ID
        queues:
            my_queue_key:
                provider: ironmq #or aws or in_band or another_aws_provider
                options:
                    queue_name:             my_actual_queue_name
                    push_notifications:     true
                    notification_retries:   3
                    message_delay:          0
                    message_timeout:        30
                    message_expiration:     604800
                    messages_to_receive:    1
                    receive_wait_time:      3
                    subscribers:
                        - { endpoint: http://example1.com/, protocol: http }
                        - { endpoint: http://example2.com/, protocol: http }

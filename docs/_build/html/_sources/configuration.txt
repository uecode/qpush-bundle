Configure the Bundle
====================

The bundle allows you to specify different Message Queue providers - however, 
Amazon AWS and IronMQ are the only ones currently supported. 

We are actively looking to add more and would be more than happy to accept contributions.

Providers
---------

This bundle allows you to configure and use multiple supported providers with in the same 
application. Each queue that you create is attached to one of your registered providers
and can have its own configuration options.

Providers may have their own dependencies that should be added to your ``composer.json`` file.

For specific instructions on how to configure each provider, please view their documents.

.. toctree::
    :maxdepth: 1

    aws-provider
    iron-mq-provider

Caching
-------

Providers can leverage a caching layer to limit the amount of calls to the Message Queue
for basic lookup functionality for things like the Queue ARN, etc.

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
it is highly recommended that you run the included console command to build queues and warm cache from the CLI before hand.*

Queue Options
-------------

Each queue can their have own options that determine how messages are published or receieved. 
The options and their descriptions are listed below.

+--------------------------+-------------------------------------------------------------------------------------------+---------------+
| Option                   | Description                                                                               | Default Value |
+==========================+===========================================================================================+===============+
| ``push_notifications``   | Whether or not to POST notifications to subscribers of a Queue                            | ``false``     |
+--------------------------+-------------------------------------------------------------------------------------------+---------------+
| ``notification_retries`` | How many attempts notifications are resent in case of errors - if supported               | ``3``         |
+--------------------------+-------------------------------------------------------------------------------------------+---------------+
| ``message_delay``        | Time in seconds before a published Message is available to be read in a Queue             | ``0``         |
+--------------------------+-------------------------------------------------------------------------------------------+---------------+
| ``message_timeout``      | Time in seconds a worker has to delete a Message before its available to other workers    | ``30``        |
+--------------------------+-------------------------------------------------------------------------------------------+---------------+
| ``message_expiration``   | Time in seconds that Messages may remain in the Queue before being removed                | ``604800``    |
+--------------------------+-------------------------------------------------------------------------------------------+---------------+
| ``messages_to_receive``  | Maximum amount of messages that can be received when polling the queue                    | ``1``         |
+--------------------------+-------------------------------------------------------------------------------------------+---------------+
| ``receive_wait_time``    | If supported, time in seconds to leave the polling request open - for long polling        | ``3``         |
+--------------------------+-------------------------------------------------------------------------------------------+---------------+
| ``subscribers``          | An array of Subscribers, containing an ``endpoint`` and ``protocol``                      | ``empty``     |
+--------------------------+-------------------------------------------------------------------------------------------+---------------+

Example Configuration
---------------------

However, a working configuration would look like the following

.. code-block:: yaml

    uecode_qpush:
        cache_service: null
        logging_enabled: true
        providers:
            aws:
                key:
                secret:
                region:
            ironmq:
                token:
                project_id:
        queues:
            default:
                provider: aws #or ironmq
                options:
                    push_notifications:     true
                    notification_retries:   3
                    message_delay:          0
                    message_timeout:        30
                    message_expiration:     604800
                    messages_to_receive:    1
                    receive_wait_time:      3
                    subscribers:
                        - { endpoint: http://example1.com/qpush, protocol: http }
                        - { endpoint: http://example2.com/qpush, protocol: http }



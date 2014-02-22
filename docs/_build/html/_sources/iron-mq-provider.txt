IronMQ Provider
^^^^^^^^^^^^^^^

The IronMQ Provider uses its Push Queues to notify subscribers of new queued
messages with out needing to continually poll the queue.

Using a Push Queue is optional with this provider and its possible to use simple
Pull queues by utilizing the provided Console Command (``uecode:qpush::receive``) 
to poll the queue.

**Configuration**

This provider relies on the `Iron MQ <https://github.com/iron-io/iron_mq_php>`_ classes
and needs to have the library included in your ``composer.json`` file.

.. code-block:: js

    {
        require: {
            "iron-io/iron_mq_php": : "2.*"
        }
    }


Configuring the provider is very easy. It requires that you have already created
an account and have a project id. 

`Iron.io <http://www.iron.io/>`_ provides free accounts for Development, which makes
testing and using this service extremely easy.

Just include your OAuth `token` and `project_id` in the configuration and set your
queue to use the `ironmq` provider.

.. code-block:: yaml

    #app/config.yml

    uecode_qpush:
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
                        - { endpoint: http://example.com/qpush, protocol: http }

**IronMQ Push Queues**

If you set ``push_notifications`` to ``true`` in your queue config, this provider
will automatically create your Queue as a Push Queue and loop over your list of ``subscribers``,
adding them to your Queue.

This provider only supports ``http`` and ``https`` subscribers. This provider also uses the
``multicast`` setting for its Push Queues, meaning that all ``subscribers`` are notified of
the same new messages.

You can chose to have your IronMQ queues work as a Pull Queue by setting ``push_notifications`` to ``false``.
This would require you to use the ``uecode:qpush:receive`` Console Command to poll the queue.

Sync Provider
-------------

The sync provider immediately dispatches and resolves queued events. It is not intended
for production use but instead to support local development, debugging and testing
of queue-based code paths.

Configuration
^^^^^^^^^^^^^

To use the sync queue, set the ``provider`` of a given queue to ``sync``. No further
configuration is necessary.

.. code-block:: yaml

    #app/config_dev.yml

    uecode_qpush:
        queues:
            my_queue_name:
                provider: sync
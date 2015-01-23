Sync Provider
-------------

The sync provider immediately dispatches and resolves queued events. It is not intended
for production use but instead to support local development, debugging and testing
of queue-based code paths.

Configuration
^^^^^^^^^^^^^

To designate a queue as synchronous, set the ``driver`` of its provider to ``sync``. No further
configuration is necessary.

.. code-block:: yaml

    #app/config_dev.yml

    uecode_qpush:
        providers:
            in_band:
                driver: sync
        queues:
            my_queue_name:
                provider: in_band
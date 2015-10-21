File Provider
-------------

The file provider uses the filesystem to dispatches and resolves queued messages.

Configuration
^^^^^^^^^^^^^

To designate a queue as file, set the ``driver`` of its provider to ``file``. You will
need to a read-able and write-able path to store the messages.

.. code-block:: yaml

    #app/config_dev.yml

    uecode_qpush:
        providers:
            file_based:
                driver: file
                path: [Path to store messages]
        queues:
            my_queue_name:
                provider: file_based
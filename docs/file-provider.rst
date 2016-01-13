File Provider
-------------

The file provider uses the filesystem to dispatch and resolve queued messages.

Configuration
^^^^^^^^^^^^^

To designate a queue as file, set the ``driver`` of its provider to ``file``. You will
need to configure a readable and writable path to store the messages.

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
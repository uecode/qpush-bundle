Custom Provider
-------------

The custom provider allows you to use your own provider. When using this provider, your implementation must implement
``Uecode\Bundle\QPushBundle\Provider\ProviderInterface``

Configuration
^^^^^^^^^^^^^

To designate a queue as custom, set the ``driver`` of its provider to ``custom``, and the ``service`` to your service id.

.. code-block:: yaml

    #app/config_dev.yml

    uecode_qpush:
        providers:
            custom_provider:
                driver: custom
                service: YOUR_CUSTOM_SERVICE_ID
        queues:
            my_queue_name:
                provider: custom_provider
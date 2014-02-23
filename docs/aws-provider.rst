AWS Provider
^^^^^^^^^^^^

The AWS Provider uses SQS & SNS to create a Push Queue model. SNS is optional with
this provider and its possible to use just SQS by utilizing the provided Console
Command (``uecode:qpush:receive``) to poll the queue.

**Configuration**

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
            aws:
                key:    <aws key>
                secret: <aws secret>
                region: us-east-1
        queues:
            my_queue_name:
                provider: aws
                options:
                    push_notifications: true
                    subscribers:
                        - { endpoint: http://example.com/qpush, protocol: http }


**Using SNS**

If you set ``push_notifications`` to ``true`` in your queue config, this provider
will automatically create the SNS Topic, subscribe your SQS queue to it, as well
as loop over your list of ``subscribers``, adding them to your Topic.

This provider automatically handles Subscription Confirmations sent from SNS, as
long as the HTTP endpoint you've listed is externally accessible and has the QPush Bundle 
properly installed and configured.

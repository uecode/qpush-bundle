Console Commands
================

This bundle includes some Console Commands which can be used for building, destroying and polling your queues
as well as sending simple messages.

Build Command
-------------

You can use the ``uecode:qpush:build`` command to create the queues on your providers. You can specify the name of a queue
as an argument to build a single queue. This command will also warm cache which avoids the need to query the provider's API
to ensure that the queue exists. Most queue providers create commands are idempotent, so running this multiple times is not an issue.::

    $ php app/console uecode:qpush:build my_queue_name

**Note:** *By default, this bundle uses File Cache.  If you clear cache, it is highly recommended you re-run the build command to warm the cache!*

Destroy Command
---------------

You can use the ``uecode:qpush:destroy`` command to completely remove queues. You can specify the name of a queue as an argument to destroy
a single queue. If you do not specify an argument, this will destroy all queues after confirmation.::

    $ php app/console uecode:qpush:destroy my_queue_name

**Note:** *This will remove queues, even if there are still unreceived messages in the queue!*

Receive Command
---------------

You can use the ``uecode:qpush:receive`` command to poll the specified queue. This command takes the name of a queue as an argument.
Messages received from this command are dispatched through the ``EventDispatcher`` and can be handled by your tagged services the same
as Push Notifications would be.::

    $ php app/console uecode:qpush:receive my_queue_name

Publish Command
---------------

You can use the ``uecode:qpush:publish`` command to send messages to your queue from the CLI. This command takes two arguments, the name of
the queue and the message to publish. The message needs to be a json encoded string.::

    $ php app/console uecode:qpush:publish my_queue_name '{"foo": "bar"}'

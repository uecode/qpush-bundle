Overview
========

The QPush Bundle relies on the Push Queue model of Message Queues to provide asynchronous
processing in your Symfony application. This allows you to remove blocking processes from the
immediate flow of your application and delegate them to another part of your application or, say, a 
cluster of workers.

This bundle allows you to easily consume and process messages by simply tagging your service or
services and relying on Symfony's event dispatcher - without needing to run a daemon or background
process to continuously poll your queue.

Content
========

.. toctree::
    :maxdepth: 4

    installation
    configuration
    usage
    console-commands



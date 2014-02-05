QPush Bundle
=======================

###Overview

###QPush Bundle Installation

The bundle should be installed through composer.

#####Add the bundle to Composer

```json
"require": {
    "uecode/qpush-bundle": "~0.1",
}
```

#####Update AppKernel.php of your Symfony Application

Add The QPushBundle to your kernel bootstrap sequence

```php
public function registerBundles()
{
	$bundles = array(
    	// ...
    	new Uecode\Bundle\QPushBundle\QPushBundle(),
    );

    return $bundles;
}
```

####Configure the Bundle

The bundle allows you to create multiple queues, with or without the use of SNS
and provide a list of subscribers for SNS.

By default the library will attempt to use file cache, however you can pass your
own cache class, as long as its an instance of `Doctrine\Common\Cache\Cache`.
Caching allows the library to remove the latency from some API calls.

#####An example configuration might look like this:

```yaml
#app/config.yml

uecode_qpush:
    aws_credentials:
        api_key: long_string_here
        api_token: longer_string_here
        region: us-east-1
    cache_service_id: my_cache_class
    queues:
        example:
            use_sns: true
            subscribers:
                - { endpoint: http://example.com, protocol: http }
```

###Usage

Once configured, you can create messages and push them to the queue and create
services that will automatically be fired as messages are pushed to your application.

####Pushing a Message to your Queue

To push Messages into your queue, you can use the built in service methods.  For
your convenience, a custom service will be created for every Queue you configure
and can be found in the service container: 

    uecode_qpush.{queue name}

######Pushing messages

```php
#src/AcmeBundle/Controller/MyController.php

public function getId()
{
    $message = [ 
        'messages should be an array'.
        'they can be flat arrays' => [
            'or multidimensional'
        ]
    ];
    
    $this->get('uecode_qpush.example')->push($message);

    // Or you can fetch the base service and use a getter
    $this->get('qpush')->getQueue('example')->push($message);
}

```

#####Consuming Messages from your Queues

Messages are ether automatically received by your application from SNS Callbacks,
or can be picked up by Cron through an included command if you are not using SNS.

As long as you have an externally accessible HTTP or HTTPS subscriber, this library
will automatically poll for new messages in the queue on notificaiton from SNS.

Messages are available through an events.  You may have one or more service
that can consume these events per application.

This library relies on your services being tagged properly

#####Tagging your service
```yaml 

```

```php
#src/AcmeBundle/Controller/MyController.php

public function getId()
{
    $message = [ 
        'messages should be an array'.
        'they can be flat arrays' => [
            'or multidimensional'
        ]
    ];
    
    $this->get('uecode_qpush.example')->push($message);

    // Or you can fetch the base service and use a getter
    $this->get('qpush')->getQueue('example')->push($message);
}
```

###Annotation Reference

Property | Description | Example
-------- | ----------- | -------
`name` | The property name inside the 'links' attribute | `user`
`href` | The relative (path) url of the resource, including url tokens | `/user/{id}/`
`params` | An associative array of token names with their corresponding getter methods | `{ "id" = "getId" }`
`groups` | Serializer Groups, Used the same way as JMS Serializer Groups | `{ "partial", "full" }`
`type` | 'Absolute' or 'Embedded' | `absolute`

####Using Params
You can have multiple tokens in the `href`.  The `params` array should be an associative array
with keys matching the tokens in the path.  Methods listed should be methods that exist in the 
annotated class.

####Groups
Specifying `groups` allow you to control the output of the links based on 
[Exclusion Groups](http://jmsyst.com/libs/serializer/master/reference/annotations#groups)

####Embedded vs Absolute Links
While `absolute` (default value), will allows include the API Host and optional prefix, 
`embedded` urls live beneath another resource. Setting type to '`embedded` will allow you 
to have links like:

```json
{
    "_links": {
        "self": {
            "href": "http://api.example.com/api/user/1/email/1/"
        }
    }
}
```

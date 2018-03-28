Installation
============

**The bundle should be installed through composer.**

 ::

    composer require uecode/qpush-bundle

**Update AppKernel.php of your Symfony Application**

Add the ``UecodeQPushBundle`` to your kernel bootstrap sequence, in the ``$bundles`` array

.. code-block:: php

    public function registerBundles()
    {
        $bundles = array(
            // ...
            new Uecode\Bundle\QPushBundle\UecodeQPushBundle(),
        );

        return $bundles;
    }


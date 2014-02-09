<?php

namespace Uecode\Bundle\QPushBundle\Provider;

use Doctrine\Common\Cache\Cache;

use Uecode\Bundle\QpushBundle\Event\MessageEvent;

interface QueueProviderInterface
{
    /**
     * Prefix prepended to the queue names
     */
    const QPUSH_PREFIX = 'qpush';

    /**
     * Returns the name of the Queue that this Provider is for
     *
     * @return string
     */
    public function getName();

    /**
     * Returns the Queue Name prefixed with the QPush Prefix
     *
     * @return string
     */
    public function getNameWithPrefix();

    /**
     * Returns the Queue Provider name
     *
     * @return string
     */
    public function getProvider();

    /**
     * Returns the Provider's Configuration Options
     *
     * @return array
     */
    public function getOptions();

    /**
     * Returns the Cache Instance
     *
     * @return Cache
     */
    public function getCache();

    /**
     * Creates the Queue
     *
     * All Create methods are idempotent, if the resource exists, the current ARN
     * will be returned
     */
    public function create();

    /**
     * Publishes a message to the Queue
     *
     * This method should return a string MessageId or Response
     *
     * @param array $message The message to queue
     *
     * @return string
     */
    public function publish(array $message);

    /**
     * Polls the Queue for Messages
     *
     * Depending on the Provider, this method may keep the connection open for
     * a configurable amount of time, to allow for long polling.  In most cases,
     * this method is not meant to be used to long poll indefinitely, but should
     * return in reasonable amount of time
     *
     * @return array
     */
    public function receive();

    /**
     * Deletes the Queue Message
     *
     * @param mixed $id A message identifier or resource
     */
    public function delete($id);

    /**
     * Destroys a Queue and clears any Queue related Cache
     *
     * @return bool
     */
    public function destroy();
}

<?php

namespace Uecode\Bundle\QPushBundle\EventListener;

use Uecode\Bundle\QpushBundle\Event\MessageEvent;

interface MessageListener
{
    /**
     * Handles a MessageEvent from the Queue
     *
     * The MessageEvent is fired when a Message is successfully received from
     * the Queue 
     *
     * @param MessageEvent $event The SQS Message Event
     */
    public function onMessage(MessageEvent $event);
}

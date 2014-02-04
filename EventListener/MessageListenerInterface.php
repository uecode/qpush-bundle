<?php

namespace Uecode\Bundle\QPushBundle\EventListener;

use Uecode\Bundle\QpushBundle\Event\MessageEvent;

interface MessageListener
{
    /**
     * Method for custom event listeners to process SQS Messages
     *
     * When all MessageListener methods have fired, the SQS Message will 
     * automatically be removed from the Queue.
     *
     * @param   MessageEvent $event SQS Message Event
     * @return  boolean
     */
    public function onMessageRetrieved(MessageEvent $event);
}

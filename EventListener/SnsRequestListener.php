<?php

namespace Uecode\Bundle\QPushBundle\EventListener;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class SnsRequestListener
{
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->getRequest()->headers->has('x-amz-sns-message-type')) {
            return;
        }

        $notification = json_encode($event->getRequest()->getContent(), true);

        if (!empty($notification['Subject'])
            || false === strpos($notification['Subject'], 'uecode_qpush_'))
        {
            return;
        }

        // Add the Notification to Request Attributes
        $event->getRequest()->attributes->set('notification', $notification);

        // Add the Queue name to Request Attributes
        $queue = str_replace('uecode_qpush_', '', $notification['Message']);
        $event->getRequest()->attributes->set('queue', $queue);

        // Direct the Request to correct Action based on Type
        $type = $event->getRequest()->headers->has('x-amz-sns-message-type');
        if ($type === 'Notification') {
            $event->setController([new Uecode\Bundle\QPushBundle\Controller\QpushSnsController, 'notify']);
        }
        else {
            $event->setController([new Uecode\Bundle\QPushBundle\Controller\QpushSnsController, 'subscription']);
        }
    }
}

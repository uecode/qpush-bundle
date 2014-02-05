<?php

namespace Uecode\Bundle\QPushBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;

use Uecode\Bundle\QPushBundle\Controller\QPushController;

class ControllerListener
{
    private $resolver;

    public function __construct(ControllerResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        if (!$event->getRequest()->headers->has('x-amz-sns-message-type')) {
            return;
        }

        $notification = json_decode($event->getRequest()->getContent(), true);

        if (false === strpos($notification['TopicArn'], 'uecode_qpush_')) {
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
            $fakeRequest = $event->getRequest()->duplicate(
                null, null, ['_controller' => 'QPushBundle:QPush:notify']
            );
            $controller = $this->resolver->getController($fakeRequest);
        } else {
            $fakeRequest = $event->getRequest()->duplicate(
                null, null, ['_controller' => 'QPushBundle:QPush:subscription']
            );
            $controller = $this->resolver->getController($fakeRequest);
        }
        $event->setController($controller);
    }
}

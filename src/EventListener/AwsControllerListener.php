<?php

namespace Uecode\Bundle\QPushBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Uecode\Bundle\QPushBundle\Controller\QPushController;

class AwsControllerListener
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

        $type = $event->getRequest()->headers->get('x-amz-sns-message-type');

        if ($type === 'Notification') {

            $queue = str_replace('uecode_qpush_', '', $notification['Message']);

            $fakeRequest = $event->getRequest()->duplicate(
                null, null, ['_controller' => 'UecodeQPushBundle:AwsEvent:notify']
            );
            $controller = $this->resolver->getController($fakeRequest);
        } else {

            $arnParts = explode(':', $notification['TopicArn']);
            $last = end($arnParts);
            $queue = str_replace('uecode_qpush_', '', $last);

            $fakeRequest = $event->getRequest()->duplicate(
                null, null, ['_controller' => 'UecodeQPushBundle:AwsEvent:subscription']
            );
            $controller = $this->resolver->getController($fakeRequest);
        }

        $event->getRequest()->attributes->set('notification', $notification);
        $event->getRequest()->attributes->set('queue', $queue);

        $event->setController($controller);
    }
}

<?php

/**
 * Copyright 2014 Underground Elephant
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @package     qpush-bundle
 * @copyright   Underground Elephant 2014
 * @license     Apache License, Version 2.0
 */

namespace Uecode\Bundle\QPushBundle\Tests\EventListener;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Uecode\Bundle\QPushBundle\EventListener\RequestListener;

/**
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class RequestListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * @var MockInterface
     */
    protected $event;

    public function setUp()
    {
        $listener         = new RequestListener($this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface'));
        $this->dispatcher = new EventDispatcher('UTF-8');
        $this->dispatcher->addListener(KernelEvents::REQUEST, [$listener, 'onKernelRequest']);

        $this->kernel     = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
    }

    public function testListenerDoesNothingForSubRequests()
    {
        $event = new GetResponseEvent($this->kernel, new Request(), HttpKernelInterface::SUB_REQUEST);
        $this->dispatcher->dispatch(KernelEvents::REQUEST, $event);

        $this->assertFalse($event->hasResponse());
    }

    public function testListenerHandlesIronMQMessageRequests()
    {
        $message = ['test' => '{"foo": "bar"}'];

        $request = new Request([],[],[],[],[],[], json_encode($message));
        $request->headers->set('iron-message-id', 123);
        $request->headers->set('iron-subscriber-message-id', 456);
        $request->headers->set('iron-subscriber-message-url', 'http://foo.bar');

        $event = new GetResponseEvent($this->kernel, $request, HttpKernelInterface::MASTER_REQUEST);
        $this->dispatcher->dispatch(KernelEvents::REQUEST, $event);

        $this->assertTrue($event->hasResponse());
        $this->assertEquals("IronMQ Notification Received.", $event->getResponse()->getContent());
    }

    public function testListenerHandlesAwsNotificationRequests()
    {
        $message = [
            'Type'      => 'Notification',
            'MessageId' => 123,
            'TopicArn'  => 'SomeArn',
            'Subject'   => 'Test',
            'Message'   => 'Test Message',
            'Timestamp' => date('Y-m-d H:i:s')
        ];

        $request = new Request([],[],[],[],[],[], json_encode($message));
        $request->headers->set('x-amz-sns-message-type', 'Notification');

        $event = new GetResponseEvent($this->kernel, $request, HttpKernelInterface::MASTER_REQUEST);
        $this->dispatcher->dispatch(KernelEvents::REQUEST, $event);

        $this->assertTrue($event->hasResponse());
        $this->assertEquals("SNS Message Notification Received.", $event->getResponse()->getContent());
    }

    public function testListenerHandlesAwsSubscriptionRequests()
    {
        $message = [
            'Type'         => 'SubscriptionConfirmation',
            'MessageId'    => 123,
            'Token'        => 456,
            'TopicArn'     => 'SomeArn',
            'SubscribeUrl' => 'http://foo.bar',
            'Subject'      => 'Test',
            'Message'      => 'Test Message',
            'Timestamp'    => date('Y-m-d H:i:s')
        ];

        $request = new Request([],[],[],[],[],[], json_encode($message));
        $request->headers->set('x-amz-sns-message-type', 'SubscriptionConfirmation');

        $event = new GetResponseEvent($this->kernel, $request, HttpKernelInterface::MASTER_REQUEST);
        $this->dispatcher->dispatch(KernelEvents::REQUEST, $event);

        $this->assertTrue($event->hasResponse());
        $this->assertEquals("SNS Subscription Confirmation Received.", $event->getResponse()->getContent());
    }
}

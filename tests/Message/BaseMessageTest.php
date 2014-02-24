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

namespace Uecode\Bundle\QpushBundle\Tests\Message;

use Uecode\Bundle\QPushBundle\Message;

/**
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
abstract class BaseMessageTest extends \PHPUnit_Framework_TestCase
{
    protected $message;

    /**
     * Tests that the Constructor accepts only an array Metadata property
     *
     * @expectedException PHPUnit_Framework_Error
     */
    abstract public function testConstructor();

    /**
     * Test that the Message Id is a String or Integer
     */
    public function testGetId()
    {
        $id = $this->message->getId();

        $this->assertContains(gettype($id), ['string', 'integer']);
    }

    /**
     * Test that the Message Body is a String or Array
     */
    public function testGetBody()
    {
        $body = $this->message->getBody();

        $this->assertContains(gettype($body), ['string', 'array']);
    }

    /**
     * Test that the Message Metadata is an ArrayCollection
     */
    public function testGetMetadata()
    {
        $metadata = $this->message->getMetadata();

        $this->assertInstanceOf('Doctrine\\Common\\Collections\\ArrayCollection', $metadata);
    }
}

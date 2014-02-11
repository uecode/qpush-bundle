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

namespace Uecode\Bundle\QPushBundle\Message;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Message
 *
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class Message
{
    /**
     * Message Id
     *
     * @var int|string
     */
    protected $id;

    /**
     * Message Body
     *
     * @var string|array
     */
    protected $body;

    /**
     * Message Metadata
     *
     * @var ArrayCollection
     */
    protected $metadata;

    /**
     * Constructor.
     *
     * Sets the Message Id, Message Body, and any Message Metadata
     *
     * @param int|string    $id         The Message Id
     * @param string\array  $body       The Message Message
     * @param array         $metadata   The Message Metadata
     */
    public function __construct($id, $body, array $metadata)
    {
        $this->id       = $id;
        $this->body     = $body;
        $this->metadata = new ArrayCollection($metadata);
    }

    /**
     * Returns the Message Id
     *
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the Message Body
     *
     * @return string|array
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Returns the Message Metadata
     *
     * @return array
     */
    public function getMetadata()
    {
        return $this->metadata;
    }
}

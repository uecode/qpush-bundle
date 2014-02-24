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
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class Notification
{
    /**
     * Notification Id
     *
     * @var int|string
     */
    protected $id;

    /**
     * Notification Body
     *
     * @var string|array
     */
    protected $body;

    /**
     * Notification Metadata
     *
     * @var ArrayCollection
     */
    protected $metadata;

    /**
     * Constructor.
     *
     * Sets the Notification Id, Notification Body, and any Notification Metadata
     *
     * @param int|string   $id       The Notification Id
     * @param string|array $body     The Notification Message
     * @param array        $metadata The Notification Metadata
     */
    public function __construct($id, $body, array $metadata)
    {
        $this->id       = $id;
        $this->metadata = new ArrayCollection($metadata);

        $message = is_string($body) ? json_decode($body, true) : $body;
        if (json_last_error() !== JSON_ERROR_NONE) {
            $message = $body;
        }

        $this->body = $message;
    }

    /**
     * Returns the Notification Id
     *
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the Notification Body
     *
     * @return string|array
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Returns the Notification Metadata
     *
     * @return ArrayCollection
     */
    public function getMetadata()
    {
        return $this->metadata;
    }
}

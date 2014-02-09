<?php

namespace Uecode\Bundle\QPushBundle\Message;

use Doctrine\Common\Collections\ArrayCollection;

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
     * @param int|string    $id         The Notification Id
     * @param string\array  $body       The Notification Message
     * @param array         $metadata   The Notification Metadata
     */
    public function __construct($id, $body, array $metadata)
    {
        $this->id       = $id;
        $this->body     = $body;
        $this->metadata = new ArrayCollection($metadata);
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
     * @return array
     */
    public function getMetadata()
    {
        return $this->metadata;
    }
}

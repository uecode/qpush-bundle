<?php

namespace Uecode\Bundle\QPushBundle\Message;

use Doctrine\Common\Collections\ArrayCollection;

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

<?php

namespace Uecode\Bundle\QPushBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index as Index;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @ORM\Table(name="uecode_qpush_message",
 * indexes={@ORM\Index(name="uecode_qpush_queue_idx",columns={"queue"}),
 *          @ORM\Index(name="uecode_qpush_delivered_idx",columns={"delivered"})})
 */
class DoctrineMessage {
    /** 
     * @ORM\Id 
     * @ORM\GeneratedValue 
     * @ORM\Column(type="integer") 
     */
    private $id;
    
     /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     */
    private $updated;
    
    /**
     *
     * @ORM\Column(type="string")
     */
    private $queue;
    
    /**
     *
     * @ORM\Column(type="boolean")
     */
    private $delivered;
    
    /**
     *
     * @ORM\Column(type="array")
     */
    private $message;

     /** 
     * @ORM\Column(type="integer") 
     */
    private $length;
    
    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set message
     *
     * @param array $message
     *
     * @return DoctrineMessage
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return array
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set queue
     *
     * @param string $queue
     *
     * @return DoctrineMessage
     */
    public function setQueue($queue)
    {
        $this->queue = $queue;

        return $this;
    }

    /**
     * Get queue
     *
     * @return string
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * Set delivered
     *
     * @param boolean $delivered
     *
     * @return DoctrineMessage
     */
    public function setDelivered($delivered)
    {
        $this->delivered = $delivered;

        return $this;
    }

    /**
     * Get delivered
     *
     * @return boolean
     */
    public function getDelivered()
    {
        return $this->delivered;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     *
     * @return DoctrineMessage
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return DoctrineMessage
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set length
     *
     * @param integer $length
     *
     * @return DoctrineMessage
     */
    public function setLength($length)
    {
        $this->length = $length;

        return $this;
    }

    /**
     * Get length
     *
     * @return integer
     */
    public function getLength()
    {
        return $this->length;
    }
}

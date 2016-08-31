<?php

namespace Uecode\Bundle\QPushBundle\Provider;

use Doctrine\Common\Cache\Cache;
use Monolog\Logger;
use Uecode\Bundle\QPushBundle\Message\Message;
use Uecode\Bundle\QPushBundle\Entity\DoctrineMessage;

class DoctrineProvider extends AbstractProvider
{

    protected $em;
    protected $repository;

    /**
     * Constructor for Provider classes
     *
     * @param string $name    Name of the Queue the provider is for
     * @param array  $options An array of configuration options for the Queue
     * @param mixed  $client  A Queue Client for the provider
     * @param Cache  $cache   An instance of Doctrine\Common\Cache\Cache
     * @param Logger $logger  An instance of Symfony\Bridge\Mongolog\Logger
     */
    public function __construct($name, array $options, $client, Cache $cache, Logger $logger)
    {
        $this->name = $name;
        $this->options = $options;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->em = $client;
        $this->repository = $this->em->getRepository('Uecode\Bundle\QPushBundle\Entity\DoctrineMessage');
    }

    /**
     * Returns the name of the Queue that this Provider is for
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the Queue Provider name
     *
     * @return string
     */
    public function getProvider()
    {
        return 'Doctrine';
    }

    /**
     * Returns the Provider's Configuration Options
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Returns the Cache service
     *
     * @return Cache
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Returns the Logger service
     *
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Get repository
     * 
     * @return array
     */
    public function getRepository()
    {
        if (!$this->repository) {
            return;
        }
        return $this->repository;
    }

    /**
     * Creates the Queue
     *
     * All Create methods are idempotent, if the resource exists, the current ARN
     * will be returned
     */
    public function create()
    {
        
    }

    /**
     * Publishes a message to the Queue
     *
     * This method should return a string MessageId or Response
     *
     * @param array $message The message to queue
     * @param  array $options An array of options that override the queue defaults
     *
     * @return string
     */
    public function publish(array $message, array $options = [])
    {
        if (!$this->em) {
            return '';
        }

        $doctrineMessage = new DoctrineMessage();
        $doctrineMessage->setQueue($this->name)
                ->setDelivered(false)
                ->setMessage($message)
                ->setLength(strlen(serialize($message)));

        $this->em->persist($doctrineMessage);
        $this->em->flush();

        return (string) $doctrineMessage->getId();
    }

    /**
     * Polls the Queue for Messages
     *
     * Depending on the Provider, this method may keep the connection open for
     * a configurable amount of time, to allow for long polling.  In most cases,
     * this method is not meant to be used to long poll indefinitely, but should
     * return in reasonable amount of time
     *
     * @param  array $options An array of options that override the queue defaults
     *
     * @return array
     */
    public function receive(array $options = [])
    {
        if (!$this->em) {
            return [];
        }

        $doctrineMessages = $this->repository->findBy(
                [
                    'delivered' => false,
                    'queue' => $this->name
                ],
                [
                    'id' => 'ASC'
                ]
        );

        $messages = [];
        foreach ($doctrineMessages as $doctrineMessage) {
            $messages[] = new Message($doctrineMessage->getId(), $doctrineMessage->getMessage(), []);
            $doctrineMessage->setDelivered(true);
        }
        $this->em->flush();

        return $messages;
    }

    /**
     * Deletes the Queue Message
     *
     * @param mixed $id A message identifier or resource
     */
    public function delete($id)
    {
        $doctrineMessage = $this->repository->findById($id);
        $doctrineMessage->setDelivered(true);
        $this->em->flush();
    }

    /**
     * Destroys a Queue and clears any Queue related Cache
     *
     * @return bool
     */
    public function destroy()
    {
        $qb = $this->repository->createQueryBuilder('dm');
        $qb->delete();
        $qb->where('dm.queue = :queue');
        $qb->setParameter('queue', $this->name);
        $qb->getQuery()->getResult();
    }

}

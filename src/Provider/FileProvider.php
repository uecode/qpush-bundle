<?php
namespace Uecode\Bundle\QPushBundle\Provider;

use Doctrine\Common\Cache\Cache;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Uecode\Bundle\QPushBundle\Event\MessageEvent;

class FileProvider extends AbstractProvider
{
    protected $filePointerList = [];

    public function __construct($name, array $options, $client, Cache $cache, Logger $logger) {
        $this->name     = $name;
        $this->options  = $options;
        $this->cache    = $cache;
        $this->logger   = $logger;
    }

    public function getProvider()
    {
        return 'File';
    }

    public function create()
    {
        $fs = new Filesystem();
        if (!$fs->exists($this->options['path'])) {
            $fs->mkdir($this->options['path']);
        }
    }

    public function publish(array $message, array $options = [])
    {
        $fileName = microtime(false);
        $path = substr(hash('md5', $fileName), 0, 3);
        $fs = new Filesystem();
        $fs->dumpFile(
            $this->options['path'].DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR.$fileName.'.json',
            json_encode($message)
        );
        return $fileName;
    }

    public function receive(array $options = [])
    {
        while (true) {
            $finder = new Finder();
            $finder = $finder
                ->files()
                ->in($this->options['path'])
                ->ignoreDotFiles(true)
                ->ignoreUnreadableDirs(true)
                ->ignoreVCS(true)
                ->depth('< 2')
                ->name('*.json')
            ;
            /** @var SplFileInfo $file */
            foreach ($finder as $file) {
                $filePointer = fopen($file->getRealPath(), 'r+');
                if (flock($filePointer, LOCK_EX | LOCK_NB)) {
                    $id = substr($file->getFilename(), 0, -5);
                    $this->filePointerList[$id] = $filePointer;
                    return json_decode($file->getContents(), true);
                }
                fclose($filePointer);
            }
            sleep(5);
        }
    }

    public function delete($id)
    {
        $fileName = $id;
        if (isset($this->filePointerList[$id])) {
            $path = substr(hash('md5', $fileName), 0, 3);
            $fs = new Filesystem();
            fclose($this->filePointerList[$id]);
            unset($this->filePointerList[$id]);
            $fs->remove(
                $this->options['path'] . DIRECTORY_SEPARATOR . $path . DIRECTORY_SEPARATOR . $fileName . '.json'
            );
        }
    }

    public function destroy()
    {
        $fs = new Filesystem();
        $fs->remove($this->options['path']);
    }

    /**
     * Removes the message from queue after all other listeners have fired
     *
     * If an earlier listener has erred or stopped propagation, this method
     * will not fire and the Queued Message should become visible in queue again.
     *
     * Stops Event Propagation after removing the Message
     *
     * @param MessageEvent $event The SQS Message Event
     * @return bool|void
     */
    public function onMessageReceived(MessageEvent $event)
    {
        $id = $event->getMessage()->getId();
        $this->delete($id);
        $event->stopPropagation();
    }
}
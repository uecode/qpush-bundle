<?php

namespace Uecode\Bundle\QPushBundle\Tests\Provider;

use Uecode\Bundle\QPushBundle\Event\MessageEvent;
use Uecode\Bundle\QPushBundle\Provider\FileProvider;

/**
 * @author James Moey <jamesmoey@gmail.com>
 */
class FileProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var FileProvider */
    protected $provider;
    protected $basePath;
    protected $queueHash;
    protected $umask;

    public function setUp()
    {
        $this->umask = umask(0);
        $this->basePath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.time().rand(0, 1000);
        mkdir($this->basePath);
        $this->provider = $this->getFileProvider();
    }

    public function tearDown()
    {
        $this->clean($this->basePath);
        umask($this->umask);
    }

    /**
     * @param string $file
     */
    protected function clean($file)
    {
        if (is_dir($file) && !is_link($file)) {
            $dir = new \FilesystemIterator($file);
            foreach ($dir as $childFile) {
                $this->clean($childFile);
            }

            rmdir($file);
        } else if (is_file($file)) {
            unlink($file);
        }
    }

    private function getFileProvider(array $options = [])
    {
        $options = array_merge(
            [
                'path'                  => $this->basePath,
                'logging_enabled'       => false,
                'message_delay'         => 0,
                'message_timeout'       => 30,
                'message_expiration'    => 604800,
                'messages_to_receive'   => 1,
            ],
            $options
        );

        $cache = $this->getMock(
            'Doctrine\Common\Cache\PhpFileCache',
            [],
            ['/tmp', 'qpush.aws.test.php']
        );

        $logger = $this->getMock(
            'Symfony\Bridge\Monolog\Logger', [], ['qpush.test']
        );

        $this->queueHash = str_replace('-', '', md5('test'));

        return new FileProvider('test', $options, null, $cache, $logger);
    }

    public function testGetProvider()
    {
        $provider = $this->provider->getProvider();

        $this->assertEquals('File', $provider);
    }

    public function testCreate()
    {
        $this->assertTrue($this->provider->create());
        $this->assertTrue(is_readable($this->basePath.DIRECTORY_SEPARATOR.$this->queueHash));
        $this->assertTrue(is_writable($this->basePath.DIRECTORY_SEPARATOR.$this->queueHash));
    }

    public function testDestroy()
    {
        $this->provider->destroy();
        $this->assertFalse(is_dir($this->basePath.DIRECTORY_SEPARATOR.$this->queueHash));
    }

    public function testReceive()
    {
        $this->provider->create();
        $this->assertTrue(is_array($this->provider->receive()));
    }

    public function testDelete()
    {
        $this->provider->create();

        $path = substr(hash('md5', '123'), 0, 3);
        mkdir($this->basePath.DIRECTORY_SEPARATOR.$this->queueHash.DIRECTORY_SEPARATOR.$path);
        touch($this->basePath.DIRECTORY_SEPARATOR.$this->queueHash.DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR.'123.json');

        $messages = $this->provider->receive();
        $this->assertNotEmpty($messages);
        $this->assertTrue($this->provider->delete(123));
    }

    public function testPublish()
    {
        $this->provider->create();
        $content = [
            ['testing'],
            ['testing 123']
        ];
        $this->provider->publish($content[0]);
        $this->provider->publish($content[1]);
        $messagesA = $this->provider->receive();
        $this->assertEquals(1, count($messagesA));
        $this->assertContains($messagesA[0]->getBody(), $content);
        $messagesB = $this->provider->receive();
        $this->assertEquals(1, count($messagesB));
        $this->assertContains($messagesB[0]->getBody(), $content);
        $this->assertNotEquals($messagesA[0]->getBody(), $messagesB[0]->getBody());
    }

    public function testPublishDelay() {
        $this->provider->create();
        $provider = $this->getFileProvider([
            'message_delay' => 2,
        ]);
        $provider->publish(['testing']);
        $messages = $provider->receive();
        $this->assertEmpty($messages);
    }

    public function testOnMessageReceived()
    {
        $this->provider->create();
        $id = $this->provider->publish(['foo' => 'bar']);
        $path = substr(hash('md5', $id), 0, 3);
        $this->assertTrue(is_file($this->basePath.DIRECTORY_SEPARATOR.$this->queueHash.DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR.$id.'.json'));
        $this->provider->onMessageReceived(new MessageEvent(
            'test',
            $this->provider->receive()[0]
        ));
        $this->assertFalse(is_file($this->basePath.DIRECTORY_SEPARATOR.$this->queueHash.DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR.$id.'.json'));
    }

    public function testCleanUp()
    {
        $this->provider->create();
        $provider = $this->getFileProvider([
            'message_expiration' => 1,
        ]);
        $provider->publish(['testing']);
        $provider->publish(['testing 123']);
        sleep(1);
        $provider->cleanUp();
        $messages = $provider->receive();
        $this->assertEmpty($messages);
    }
}
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

namespace Uecode\Bundle\QPushBundle\Tests\Provider;

use Doctrine\Common\Cache\PhpFileCache;
use Symfony\Bridge\Monolog\Logger;

use Uecode\Bundle\QpushBundle\Provider\ProviderInterface;

use Uecode\Bundle\QPushBundle\Message;

/**
 * AbstractProviderTest
 *
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class AbstractProviderTest extends \PHPUnit_Framework_TestCase
{
    protected $provider;

    public function setUp()
    {
        $this->provider = new TestProvider(
            'test', 
            ['logging_enabled' => false],
            new \stdClass,
            new PhpFileCache('/tmp', 'qpush.test.php'),
            new Logger('qpush.test')
        );  
    }

    public function tearDown()
    {
        $this->provider = null;
    }

    public function testGetName()
    {
        $name = $this->provider->getName();

        $this->assertEquals($name, 'test');
    }

    public function testGetNameWithPrefix()
    {
        $name = $this->provider->getNameWithPrefix();

        $this->assertEquals(sprintf('%s_%s', ProviderInterface::QPUSH_PREFIX, 'test'), $name);
    }

    public function testGetOptions()
    {
        $options = $this->provider->getOptions();

        $this->assertTrue(is_array($options));
        $this->assertEquals(['logging_enabled' => false], $options);
    }

    public function testGetCache()
    {
        $cache = $this->provider->getCache();

        $this->assertInstanceOf('Doctrine\\Common\\Cache\\Cache', $cache);
    }

    public function testGetLogger()
    {
        $logger = $this->provider->getLogger();

        $this->assertInstanceOf('Monolog\\Logger', $logger);
    }

    public function testLogEnabled()
    {
        $this->assertFalse($this->provider->log(100, 'test log', []));

        $provider = new TestProvider(
            'test', 
            ['logging_enabled' => true],
            new \stdClass,
            new PhpFileCache('/tmp', 'qpush.test.php'),
            new Logger('qpush.test')
        );  
        $this->assertTrue($provider->log(100, 'test log', []));
    }

    public function testGetProvider()
    {
        $provider = $this->provider->getProvider();

        $this->assertEquals('TestProvider', $provider);
    }
}

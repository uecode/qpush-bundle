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

namespace Uecode\Bundle\QPushBundle\Tests\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Uecode\Bundle\QPushBundle\DependencyInjection\UecodeQPushExtension;

/**
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class UecodeQPushExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * QPush Extension
     *
     * @var UecodeQPushExtension
     */
    private $extension;

    /**
     * Container
     *
     * @var ContainerBuilder
     */
    private $container;

    public function setUp()
    {
        $this->extension = new UecodeQPushExtension();
        $this->container = new ContainerBuilder(new ParameterBag([
            'kernel.cache_dir' => '/tmp'
        ]));

        $this->container->registerExtension($this->extension);
    }

    public function testConfiguration()
    {
        $loader = new YamlFileLoader($this->container, new FileLocator(__DIR__.'/../Fixtures/'));
        $loader->load('config_test.yml');

        $this->container->compile();

        $this->assertTrue($this->container->has('uecode_qpush'));

        $this->assertTrue($this->container->has('uecode_qpush.test_aws'));
        $this->assertTrue($this->container->has('uecode_qpush.test_file'));
        $this->assertTrue($this->container->has('uecode_qpush.test_secondary_aws'));
        $this->assertNotSame(
            $this->container->get('uecode_qpush.test_aws'),
            $this->container->get('uecode_qpush.test_secondary_aws')
        );

        $this->assertTrue($this->container->has('uecode_qpush.test_ironmq'));
        $this->assertTrue($this->container->has('uecode_qpush.test_secondary_ironmq'));
        $this->assertNotSame(
            $this->container->get('uecode_qpush.test_ironmq'),
            $this->container->get('uecode_qpush.test_secondary_ironmq')
        );
    }
}

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

use Uecode\Bundle\QPushBundle\Provider\ProviderRegistry;

/**
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class ProviderRegistryTest extends \PHPUnit_Framework_TestCase
{
    public function testRegistry()
    {
        $registry   = new ProviderRegistry();
        $interface  = 'Uecode\Bundle\QPushBundle\Provider\ProviderInterface';

        $registry->addProvider('test', $this->getMock($interface));

        $this->assertEquals(['test' => $this->getMock($interface)], $registry->all());

        $this->assertTrue($registry->has('test'));

        $this->assertEquals($this->getMock($interface), $registry->get('test'));

        $this->setExpectedException('InvalidArgumentException');
        $registry->get('foo');
    }
}

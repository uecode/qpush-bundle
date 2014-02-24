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

namespace Uecode\Bundle\QPushBundle\Tests\MockClient;

/**
 * @codeCoverageIgnore
 *
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class AwsMockClient extends \Aws\Common\Aws
{
    public function get($name, $throwAway = false)
    {
        if (!in_array($name, ['Sns', 'Sqs'])) {
            throw new \InvalidArgumentException(
                sprintf('Only supports Sns and Sqs as options, %s given.', $name)
            );
        }

        if ($name == "Sns") {
            return new SnsMockClient;
        }

        return new SqsMockClient;
    }
}

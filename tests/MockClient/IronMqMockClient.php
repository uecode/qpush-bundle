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
class IronMqMockClient
{
    private $deleteCount = 0;

    public function createQueue($queue, array $params = [])
    {
        $response                   = new \stdClass;
        $response->id               = '530295fe3c94fbcf0c79cffe';
        $response->name             = 'test';
        $response->size             = 0;
        $response->total_messages   = 0;
        $response->project_id       = '52f67d032001c00005000057';

        return $response;
    }

    public function deleteQueue($queue)
    {
        if ($this->deleteCount == 0) {
            $this->deleteCount++;

            return true;
        }

        if ($this->deleteCount == 1) {
            $this->deleteCount++;

            throw new \Exception('http error: 404 | {"msg":"Queue not found"}');
        }

        throw new \Exception('Random Exception');
    }

    public function postMessage($queue, $message, $options)
    {
        $response       = new \stdClass;
        $response->id   = 123;
        $response->ids  = [123];
        $response->msg  = "Messages put on queue.";

        return $response;
    }

    public function getMessages($queue, $count, $timeout)
    {
        $response                   = new \stdClass;
        $response->id               = 123;
        $response->body             = '{"foo":"bar","_qpush_queue":"test"}';
        $response->timeout          = 60;
        $response->reserved_count   = 1;
        $response->push_status      = new \stdClass;

        return [$response];
    }

    public function deleteMessage($queue, $id)
    {
        $response = new \stdClass;
        $response->id = $id;

        if ($id == 456) {
            throw new \Exception('http error: 404 | {"msg":"Queue not found"}');
        }

        if ($id == 789) {
            throw new \Exception('Random Exception');
        }

        return $response;
    }

    public function getQueue($queue)
    {
        $response                   = new \stdClass;
        $response->id               = '530295fe3c94fbcf0c79cffe';
        $response->name             = 'test';
        $response->size             = 0;
        $response->total_messages   = 0;
        $response->project_id       = '52f67d032001c00005000057';

        return $response;
    }
}

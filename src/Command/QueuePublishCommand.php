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

namespace Uecode\Bundle\QPushBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class QueuePublishCommand extends ContainerAwareCommand
{
    protected $output;

    protected function configure()
    {
        $this
            ->setName('uecode:qpush:publish')
            ->setDescription('Sends a Message to a Queue')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Name of the Queue'
            )
            ->addArgument(
                'message',
                InputArgument::REQUIRED,
                'A JSON encoded Message to send to the Queue'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $registry = $this->getContainer()->get('uecode_qpush');

        $name = $input->getArgument('name');
        $message = $input->getArgument('message');

        $this->sendMessage($registry, $name, $message);

        return 0;
    }

    private function sendMessage($registry, $name, $message)
    {
        if (!$registry->has($name)) {
            $this->output->writeln(
                sprintf("The [%s] queue you have specified does not exists!", $name)
            );

            return 1;
        }

        $registry->get($name)->publish(json_decode($message, true));

        $this->output->writeln("<info>The message has been sent.</info>");

        return 0;
    }
}

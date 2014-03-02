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
use Uecode\Bundle\QPushBundle\Event\Events;
use Uecode\Bundle\QPushBundle\Event\MessageEvent;

/**
 * @author Luis Cordova <cordoval@gmail.com>
 */
class QueueStatusCommand
{
    protected function configure()
    {
        $this
            ->setName('uecode:qpush:status')
            ->setDescription('Check the status(es) of the queue(s)')
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                'Name of a specific queue to check',
                null
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $registry = $this->getContainer()->get('uecode_qpush');

        $name = $input->getArgument('name');

        if (null !== $name) {
            return $this->getStatus($registry, $name);
        }

        foreach ($registry->all() as $queue) {
            $this->getStatus($registry, $queue->getName());
        }

        return 0;
    }

    private function getStatus($registry, $name)
    {
        if (!$registry->has($name)) {
            $this->output->writeln(
                sprintf("The [%s] queue you have specified does not exist!", $name)
            );

            return 1;
        }

        $status = $registry->get($name)->ge();

        $msg = "<info>Finished checking status for %s Queue: %s.</info>";
        $this->output->writeln(sprintf($msg, $name, $status));

        return 0;
    }
}

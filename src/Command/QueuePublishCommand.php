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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class QueuePublishCommand extends Command implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     *
     * @api
     */
    protected $container;

    /**
     * Sets the Container associated with this Controller.
     *
     * @param ContainerInterface $container A ContainerInterface instance
     *
     * @api
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

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
        $registry = $this->container->get('uecode_qpush');

        $name = $input->getArgument('name');
        $message = $input->getArgument('message');

        return $this->sendMessage($registry, $name, $message);
    }

    private function sendMessage($registry, $name, $message)
    {
        if (!$registry->has($name)) {
            return $this->output->writeln(
                sprintf("The [%s] queue you have specified does not exists!", $name)
            );
        }

        $registry->get($name)->publish(json_decode($message, true));
        $this->output->writeln("<info>The message has been sent.</info>");

        return 0;
    }
}

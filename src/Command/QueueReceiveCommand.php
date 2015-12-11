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
use Uecode\Bundle\QPushBundle\Event\Events;
use Uecode\Bundle\QPushBundle\Event\MessageEvent;

/**
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class QueueReceiveCommand extends Command implements ContainerAwareInterface
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
            ->setName('uecode:qpush:receive')
            ->setDescription('Polls the configured Queues')
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                'Name of a specific queue to poll',
                null
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $registry = $this->container->get('uecode_qpush');

        $name = $input->getArgument('name');

        if (null !== $name) {
            return $this->pollQueue($registry, $name);
        }

        foreach ($registry->all() as $queue) {
            $this->pollQueue($registry, $queue->getName());
        }

        return 0;
    }

    private function pollQueue($registry, $name)
    {
        if (!$registry->has($name)) {
            return $this->output->writeln(
                sprintf("The [%s] queue you have specified does not exists!", $name)
            );
        }

        $dispatcher = $this->container->get('event_dispatcher');
        $messages   = $registry->get($name)->receive();

        foreach ($messages as $message) {
            $messageEvent = new MessageEvent($name, $message);
            $dispatcher->dispatch(Events::Message($name), $messageEvent);
        }

        $msg = "<info>Finished polling %s Queue, %d messages fetched.</info>";
        $this->output->writeln(sprintf($msg, $name, sizeof($messages)));

        return 0;
    }
}

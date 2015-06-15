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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class QueueDestroyCommand extends Command implements ContainerAwareInterface
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
            ->setName('uecode:qpush:destroy')
            ->setDescription('Destroys the configured Queues and cleans Cache')
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                'Name of a specific queue to destroy',
                null
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Set this parameter to force this action'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $registry = $this->container->get('uecode_qpush');
        $dialog = $this->getHelperSet()->get('dialog');

        $name = $input->getArgument('name');

        if (null !== $name) {
            if (!$input->getOption('force')) {
                $response = $dialog->askConfirmation(
                    $output,
                    sprintf(
                        '<comment>This will remove the %s queue, even if it has messages! Are you sure? </comment>',
                        $name
                    ),
                    false
                );

                if (!$response) {
                    return 0;
                }
            }

            return $this->destroyQueue($registry, $name);
        }

        if (!$input->getOption('force')) {
            $response = $dialog->askConfirmation(
                $output,
                '<comment>This will remove ALL queues, even if they have messages.  Are you sure? </comment>',
                false
            );

            if (!$response) {
                return 0;
            }
        }

        foreach ($registry->all() as $queue) {
            $this->destroyQueue($registry, $queue->getName());
        }

        return 0;
    }

    private function destroyQueue($registry, $name)
    {
        if (!$registry->has($name)) {
            return $this->output->writeln(
                sprintf("The [%s] queue you have specified does not exists!", $name)
            );
        }

        $registry->get($name)->destroy();
        $this->output->writeln(sprintf("The %s queue has been successfully destroyed.", $name));

        return 0;
    }
}

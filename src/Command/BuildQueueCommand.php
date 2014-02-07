<?php

namespace Uecode\Bundle\QPushBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuildQueueCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('qpush:build:queue')
            ->setDescription('Builds the configured Queues')
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                'Name of a specific queue to build',
                null
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $registry = $this->getContainer()->get('uecode_qpush');

        $name = $input->getArgument('name');

        if (null !== $name) {
            return $this->buildQueue($registry, $name);
        }

        foreach ($registry->all() as $queue) {
            $this->buildQueue($registry, $queue->getName());
        }

    }

    private function buildQueue($registry, $name)
    {
        if (!$registry->has($name)) {
            return $output->writeln(
                sprintf("This [%s] is not the queue you are looking for...", $name)
            );
        }

        $registry->get($name)->create();
        $this->output->writeln(sprintf("The %s queue has been built successfully.", $name));

        return 0;
    }
}

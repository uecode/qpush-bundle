<?php

namespace Uecode\Bundle\QPushBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Uecode\Bundle\QPushBundle\Event\Events;
use Uecode\Bundle\QPushBundle\Event\MessageEvent;

class QueueReceiveCommand extends ContainerAwareCommand
{
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
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $registry = $this->getContainer()->get('uecode_qpush');

        $name = $input->getArgument('name');

        if (null !== $name) {
            return $this->pollQueue($registry, $name);
        }

        foreach ($registry->all() as $queue) {
            $this->pollQueue($registry, $queue->getName());
        }

    }

    private function pollQueue($registry, $name)
    {
        if (!$registry->has($name)) {
            return $this->output->writeln(
                sprintf("The [%s] queue you have specified does not exists!", $name)
            );
        }

        $provider   = $registry->get($name);
        $messages   = $provider->receive();
        $count      = sizeof($messages);
        foreach ($messages as $message) {

            $messageEvent   = new MessageEvent($name, $message);
            $dispatcher     = $this->getContainer()->get('event_dispatcher');

            $dispatcher->dispatch(Events::Message($name), $messageEvent);
        }

        $msg = "<info>Finished polling %s Queue, %d messages fetched.</info>";
        $this->output->writeln(sprintf($msg, $name, $count));

        return 0;
    }
}

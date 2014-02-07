<?php

namespace Uecode\Bundle\QPushBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MessageCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('qpush:send:message')
            ->setDescription('Sends a Message to a Queue')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Name of the Queue'
            )
            ->addArgument(
                'message',
                InputArgument::REQUIRED,
                'Message to send to the Queue'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $registry = $this->getContainer()->get('uecode_qpush');

        $name = $input->getArgument('name');
        $message = $input->getArgument('message');

        return $this->sendMessage($registry, $name, $message);
    }

    private function sendMessage($registry, $name, $message)
    {
        if (!$registry->has($name)) {
            return $output->writeln(
                sprintf("This [%s] is not the queue you are looking for...", $name)
            );
        }

        $registry->get($name)->publish([$message]);
        $this->output->writeln("<info>The message has been sent.</info>");

        return 0;
    }
}

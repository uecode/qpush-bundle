<?php
namespace Uecode\Bundle\QPushBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;

class QPushBundle extends Bundle
{
    /**
     * Adds the Compiler Pass for the QPushBundle
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new QPushCompilerPass());

        $listeners = $container->getParameter('uecode_qpush.event_listeners');
        foreach ($listeners as $listener) {

            $compilerPass = new RegisterListenersPass(
                'event_dispatcher', 
                'uecode_qpush.event_listener.' . $name,
                'uecode_qpush.event_subscriber.' . $name
            );

            $container->addCompilerPass($compilerPass);
        }
    }
}

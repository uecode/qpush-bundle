<?php
namespace Uecode\Bundle\QPushBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Uecode\Bundle\QPushBundle\DependencyInjection\QPushCustomExtension;
use Uecode\Bundle\QPushBundle\DependencyInjection\Compiler\QPushCompilerPass;

class QPushBundle extends Bundle
{
    /**
     * Adds the Compiler Pass for the QPushBundle
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->registerExtension(new QPushCustomExtension);
        $container->addCompilerPass(new QPushCompilerPass);
    }
}

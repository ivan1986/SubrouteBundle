<?php

namespace Ivan1986\SubrouteBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SubrouteBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new DependencyInjection\Compiler\RouterDefPass());
    }

}

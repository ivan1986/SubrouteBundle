<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ivan
 * Date: 18.02.13
 * Time: 17:03
 * To change this template use File | Settings | File Templates.
 */

namespace Ivan1986\SubrouteBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class RouterDefPass implements CompilerPassInterface {
    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     *
     * @api
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('router.default')) {
            return;
        }
        $def = $container->getDefinition('router.default');
        $routes = $container->getParameter('subrouter.router_files');
        foreach($routes as $name=>$file)
        {
            $sr = new Definition($def->getClass(), $def->getArguments());
            $opt = $sr->getArgument(2);
            //change cache classes
            $opt['generator_cache_class'].='_'.$name;
            $opt['matcher_cache_class'].='_'.$name;
            $sr->replaceArgument(1, $file);
            $sr->replaceArgument(2, $opt);
            $container->setDefinition('subrouter.router.'.$name, $sr);
        }
    }

}

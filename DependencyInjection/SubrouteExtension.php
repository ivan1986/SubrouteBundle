<?php

namespace Ivan1986\SubrouteBundle\DependencyInjection;

use Ivan1986\DemoBundle\DependencyInjection\Compiler\RouterDefPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;

class SubrouteExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $container->setParameter('subrouter.router_files', $config);
    }

    public function getAlias()
    {
        return 'subroute';
    }
}

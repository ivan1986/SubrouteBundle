<?php

namespace Ivan1986\SubrouteBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SubrouteDebugCommand extends ContainerAwareCommand
{
    /**
     * {@inheritDoc}
     */
    public function isEnabled()
    {
        $routes = $this->getContainer()->getParameter('subrouter.router_files');
        if (empty($routes)) {
            return false;
        }
        return parent::isEnabled();
    }

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('subroute:debug')
            ->setDefinition(array(
                new InputArgument('router', InputArgument::OPTIONAL, 'A router name'),
                new InputArgument('name', InputArgument::OPTIONAL, 'A route name'),
                new InputOption('list', 'l', InputOption::VALUE_NONE, 'list routers')
            ))
            ->setDescription('Displays subrouters for an application')
            ->setHelp(<<<EOF
The <info>%command.name%</info> displays the configured subrouters:

  <info>php %command.full_name%</info>
EOF
            )
        ;
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $router = $input->getArgument('router');
        $name = $input->getArgument('name');
        $list = $input->getOption('list');
        if ($router === null || $list)
        {
            $this->outputRouters($output);
            return;
        }
        if (!$this->getContainer()->has('subrouter.router.'.$router))
        {
            throw new \InvalidArgumentException(sprintf('The router "%s" does not exist.', $router));
        }

        if ($name) {
            $this->outputRoute($output, $router, $name);
        } else {
            $this->outputRoutes($output, $router);
        }
    }

    private function outputRouters(OutputInterface $output)
    {
        $routes = $this->getContainer()->getParameter('subrouter.router_files');
        $output->writeln($this->getHelper('formatter')->formatSection('routers', 'Current routers'));

        $maxName = strlen('name');
        foreach($routes as $name => $file)
        {
            $maxName = max($maxName, strlen($name));
        }
        $format  = '%-'.$maxName.'s %s';
        $format1  = '%-'.($maxName + 19).'s %s';
        $output->writeln(sprintf($format1, '<comment>Name</comment>', '<comment>File</comment>'));
        foreach($routes as $name => $file)
        {
            $output->writeln(sprintf($format, $name, $file));
        }
    }

    protected function outputRoutes(OutputInterface $output, $router, $routes = null)
    {
        if (null === $routes) {
            $routes = $this->getContainer()->get('subrouter.router.'.$router)->getRouteCollection()->all();
        }

        $output->writeln($this->getHelper('formatter')->formatSection('router', 'Current routes'));

        $maxName = strlen('name');
        $maxMethod = strlen('method');
        $maxHost = strlen('host');

        foreach ($routes as $name => $route) {
            $requirements = $route->getRequirements();
            $method = isset($requirements['_method'])
                ? strtoupper(is_array($requirements['_method'])
                    ? implode(', ', $requirements['_method']) : $requirements['_method']
                )
                : 'ANY';
            $host = '' !== $route->getHost() ? $route->getHost() : 'ANY';
            $maxName = max($maxName, strlen($name));
            $maxMethod = max($maxMethod, strlen($method));
            $maxHost = max($maxHost, strlen($host));
        }
        $format  = '%-'.$maxName.'s %-'.$maxMethod.'s %-'.$maxHost.'s %s';

        // displays the generated routes
        $format1  = '%-'.($maxName + 19).'s %-'.($maxMethod + 19).'s %-'.($maxHost + 19).'s %s';
        $output->writeln(sprintf($format1, '<comment>Name</comment>', '<comment>Method</comment>', '<comment>Host</comment>', '<comment>Pattern</comment>'));
        foreach ($routes as $name => $route) {
            $requirements = $route->getRequirements();
            $method = isset($requirements['_method'])
                ? strtoupper(is_array($requirements['_method'])
                    ? implode(', ', $requirements['_method']) : $requirements['_method']
                )
                : 'ANY';
            $host = '' !== $route->getHost() ? $route->getHost() : 'ANY';
            $output->writeln(sprintf($format, $name, $method, $host, $route->getPath()));
        }
    }

    /**
     * @throws \InvalidArgumentException When route does not exist
     */
    protected function outputRoute(OutputInterface $output, $router, $name)
    {
        $route = $this->getContainer()->get('subrouter.router.'.$router)->getRouteCollection()->get($name);
        if (!$route) {
            throw new \InvalidArgumentException(sprintf('The route "%s" does not exist in router "%s".', $name, $router));
        }

        $host = '' !== $route->getHost() ? $route->getHost() : 'ANY';

        $output->writeln($this->getHelper('formatter')->formatSection('router', sprintf('Route "%s"', $name)));

        $output->writeln(sprintf('<comment>Name</comment>         %s', $name));
        $output->writeln(sprintf('<comment>Pattern</comment>      %s', $route->getPath()));
        $output->writeln(sprintf('<comment>Host</comment>         %s', $host));
        $output->writeln(sprintf('<comment>Class</comment>        %s', get_class($route)));

        $defaults = '';
        $d = $route->getDefaults();
        ksort($d);
        foreach ($d as $name => $value) {
            $defaults .= ($defaults ? "\n".str_repeat(' ', 13) : '').$name.': '.$this->formatValue($value);
        }
        $output->writeln(sprintf('<comment>Defaults</comment>     %s', $defaults));

        $requirements = '';
        $r = $route->getRequirements();
        ksort($r);
        foreach ($r as $name => $value) {
            $requirements .= ($requirements ? "\n".str_repeat(' ', 13) : '').$name.': '.$this->formatValue($value);
        }
        $requirements = '' !== $requirements ? $requirements : 'NONE';
        $output->writeln(sprintf('<comment>Requirements</comment> %s', $requirements));

        $options = '';
        $o = $route->getOptions();
        ksort($o);
        foreach ($o as $name => $value) {
            $options .= ($options ? "\n".str_repeat(' ', 13) : '').$name.': '.$this->formatValue($value);
        }
        $output->writeln(sprintf('<comment>Options</comment>      %s', $options));
        $output->write('<comment>Regex</comment>        ');
        $output->writeln(preg_replace('/^             /', '', preg_replace('/^/m', '             ', $route->compile()->getRegex())), OutputInterface::OUTPUT_RAW);
    }

    protected function formatValue($value)
    {
        if (is_object($value)) {
            return sprintf('object(%s)', get_class($value));
        }

        if (is_string($value)) {
            return $value;
        }

        return preg_replace("/\n\s*/s", '', var_export($value, true));
    }

}

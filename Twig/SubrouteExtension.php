<?php

namespace Ivan1986\SubrouteBundle\Twig;

use Ivan1986\SubrouteBundle\Routing\Subrouting;

class SubrouteExtension extends \Twig_Extension
{
    /** @var Subrouting */
    private $subroute;

    public function __construct(Subrouting $subroute)
    {
        $this->subroute = $subroute;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return array(
            'mysubpath'  => new \Twig_Function_Method($this, 'getMySubPath'),
            'subpath' => new \Twig_Function_Method($this, 'getSubPath'),
        );
    }

    public function getMySubPath($name, $parameters = array())
    {
        return $this->subroute->generateMyUrl($name, $parameters);
    }

    public function getSubPath($route, $name, $parameters = array())
    {
        return $this->subroute->generateSubUrl($route, $name, $parameters);
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'subrouting';
    }
}
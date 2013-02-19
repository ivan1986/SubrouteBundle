<?php

namespace Ivan1986\SubrouteBundle\Routing;


use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RequestContext;

class Subrouting {

    /** @var ContainerInterface */
    protected $container;

    /** @var array */
    protected $routes;

    /** @var Router */
    protected $matcher;
    /** @var string */
    protected $curName;

        /** @var array */
    protected $routesStack;

    /** @var LoggerInterface|null */
    protected $logger;


    public function __construct(ContainerInterface $container, $routers, LoggerInterface $logger = null)
    {
        $this->matcher = null;
        $this->curName = null;
        $this->logger = $logger;
        $this->routes = $routers;
        $this->container = $container;
    }

    /**
     * @param string $routerName
     * @param string $componentName
     * @param array $addParams
     * @return Response
     * @throws \InvalidArgumentException
     */
    public function route($routerName, $componentName, $addParams = array())
    {
        if (!array_key_exists($routerName, $this->routes))
            throw new \InvalidArgumentException('Subrouter: Not found router whis name "'.$routerName.'"');
        /** @var Request $request */
        $request = $this->container->get('request');
        if (!$request->attributes->has($componentName))
            throw new \InvalidArgumentException('Subrouter: Not found component whis name "'.$componentName.'" in route "'.$request->attributes->get('_route').'"');
        $path = $request->attributes->get($componentName);

        $this->routesStack[] = array(
            'path' => $path,
            'request' => $request->duplicate(),
            'routerName' => $this->curName,
            'componentName' => $componentName,
        );

        /** @var Router */
        $this->matcher = $this->container->get('subrouter.router.'.$routerName);
        $this->curName = $routerName;

        $context = new RequestContext();
        $context->fromRequest($request);
        $context->setPathInfo('/'.$path);
        $context->setBaseUrl('');
        $this->matcher->setContext($context);
        $r = $this->matcher->match($context->getPathInfo());
        $this->setController($request, $context);
        $request->attributes->add($addParams);
        //call sub request
        $result = $this->container->get('http_kernel')->handle($request);
        //restore original request
        $lastRoute = array_pop($this->routesStack);
        $this->container->set('request', $lastRoute['request']);
        $this->curName = $lastRoute['routerName'];
        return $result;
    }

    public function generateSubUrl($routerName, $route, $parameters = array())
    {
        if (!array_key_exists($routerName, $this->routes))
            throw new \InvalidArgumentException('Subrouter: Not found router whis name "'.$routerName.'"');
        $router = $this->container->get('subrouter.router.'.$routerName);
        $url = $router->generate($route, $parameters, UrlGeneratorInterface::ABSOLUTE_PATH);
        return ltrim($url, '/');
    }

    public function generateMyUrl($route, $parameters = array())
    {
        $url = $this->matcher->generate($route, $parameters, UrlGeneratorInterface::ABSOLUTE_PATH);
        $url = ltrim($url, '/');
        foreach(array_reverse($this->routesStack) as $route)
        {
            $name = $route['request']->attributes->get('_route');
            $params = $route['request']->attributes->get('_route_params');
            $params[ $route['componentName'] ] = $url;
            $router = $route['routerName'] ? $this->container->get('subrouter.router.'.$route['routerName']) : $this->container->get('router');
            $url = $router->generate($name, $params, UrlGeneratorInterface::ABSOLUTE_PATH);
            if ($route['routerName'])
                $url = ltrim($url, '/');
        }
        return $url;
    }

    // this part get from Symfony\Component\HttpKernel\EventListener\RouterListener
    //------------------------------------------------------------------------------------------------------------------

    /**
     * get controller and params from RequestContext
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\Routing\RequestContext $requestContext
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    protected function setController(Request $request, RequestContext $requestContext)
    {
        // add attributes based on the request (routing)
        try {
            $parameters = $this->matcher->match($requestContext->getPathInfo());

            if (null !== $this->logger) {
                $this->logger->info(sprintf('Matched route "%s" (parameters: %s)', $parameters['_route'], $this->parametersToString($parameters)));
            }

            foreach($request->attributes->get('_route_params') as $k=>$p)
                $request->attributes->remove($k);
            $request->attributes->add($parameters);
            unset($parameters['_route']);
            unset($parameters['_controller']);
            $request->attributes->set('_route_params', $parameters);
        } catch (ResourceNotFoundException $e) {
            $message = sprintf('No route found for "%s %s"', $request->getMethod(), $requestContext->getPathInfo());

            throw new NotFoundHttpException($message, $e);
        } catch (MethodNotAllowedException $e) {
            $message = sprintf('No route found for "%s %s": Method Not Allowed (Allow: %s)', $request->getMethod(), $requestContext->getPathInfo(), strtoupper(implode(', ', $e->getAllowedMethods())));

            throw new MethodNotAllowedHttpException($e->getAllowedMethods(), $message, $e);
        }
    }

    protected function parametersToString(array $parameters)
    {
        $pieces = array();
        foreach ($parameters as $key => $val) {
            $pieces[] = sprintf('"%s": "%s"', $key, (is_string($val) ? $val : json_encode($val)));
        }

        return implode(', ', $pieces);
    }

    //------------------------------------------------------------------------------------------------------------------

}
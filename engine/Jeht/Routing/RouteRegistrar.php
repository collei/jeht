<?php
namespace Jeht\Routing;

use Jeht\Routing\Router;
use Jeht\Ground\Application;

use Jeht\Exceptions\Http\InvalidHttpMethodException;

class RouteRegistrar
{
	/**
	 * @var string[]
	 */
	protected const HTTP_METHODS = [
		'GET','POST','PUT','PATCH','OPTIONS','HEAD','DELETE'
	];

	/**
	 * @var \Jeht\Ground\Application
	 */
	protected $app;

	/**
	 * @var \Jeht\Ground\Routing\Router
	 */
	protected $router;

	/**
	 * @var \Jeht\Ground\Routing\RouteGroup
	 */
	protected $routeGroup;

	/**
	 * @var string
	 */
	protected $appBaseUri;

	/**
	 * @var array
	 */
	protected $routeFactories = [];

	/**
	 * Aggregate uri and name segments, occasionally applying the handler
	 * if specified.
	 *
	 * @param	string	$withUriSuffix
	 * @param	string	$withName
	 * @param	mixed	$withHandler
	 * @return	array
	 */
	protected function aggregateAttributes(string $withUriSuffix, $withHandler, string $withName = null)
	{
		$current = $this->routeGroup->getCurrent();
		//
		$uri = !empty($current['prefix'])
			? ($current['prefix'] . '/' . $withUriSuffix)
			: $withUriSuffix;
		//
		$uri = str_replace('//', '/', $uri);
		//
		$withName = $withName ? trim($withName, ' 	.') : null;
		//
		$name = !empty($current['name'])
			? (rtrim($current['name'], '.') . ($withName ? ('.'.$withName) : ''))
			: $withName;
		//
		$handler = $withHandler ?? $current['handler'] ?? null;
		//
		return array($uri, $name, $handler);
	}

	/**
	 * Register a route
	 *
	 * @param	array	$methods
	 * @param	string	$uri
	 * @param	mixed	$handler
	 * @return	\Jeht\Routing\Routefactory
	 */
	protected function registerRoute(array $methods, string $uri, $handler)
	{
		// Aggregate path prefixes into a uri with the current 'suffix'
		// and also provides convenient method of override the handler
		// of the current group.
		[$uri, $name, $handler] = $this->aggregateAttributes($uri, $handler);
		//
		$factory = new RouteFactory(
			$this->appBaseUri . $uri, $methods, $handler, $name
		);
		//
		return $this->routeFactories[] = $factory;
	}

	/**
	 * Initializes the route registrar
	 *
	 * @param	\Jeht\Ground\Application	$app
	 * @param	\Jeht\Routing\Router	$router
	 * @param	\Jeht\Routing\RouteGroup	$routeGroup
	 */
	public function __construct(Application $app, Router $router, RouteGroup $routeGroup)
	{
		$this->app = $app;
		$this->router = $router;
		$this->routeGroup = $routeGroup->setRouteRegistrar($this);
		//
		$this->app->instance(Router::class, $router);
		$this->app->instance(RouteGroup::class, $routeGroup);
		//
		$this->appBaseUri = $this->app['app.rooturi'];
	}

	/**
	 * Register a GET route.
	 *
	 * @param	string	$uri
	 * @param	string|array|\Closure	$handler
	 * @return	\Jeht\Routing\RouteFactory
	 */
	public function get(string $uri, $handler = null)
	{
		return $this->registerRoute(['GET'], $uri, $handler);
	}

	/**
	 * Register a HEAD route.
	 *
	 * @param	string	$uri
	 * @param	string|array|\Closure	$handler
	 * @return	\Jeht\Routing\RouteFactory
	 */
	public function head(string $uri, $handler = null)
	{
		return $this->registerRoute(['HEAD'], $uri, $handler);
	}

	/**
	 * Register a POST route.
	 *
	 * @param	string	$uri
	 * @param	string|array|\Closure	$handler
	 * @return	\Jeht\Routing\RouteFactory
	 */
	public function post(string $uri, $handler = null)
	{
		return $this->registerRoute(['POST'], $uri, $handler);
	}

	/**
	 * Register a PATCH route.
	 *
	 * @param	string	$uri
	 * @param	string|array|\Closure	$handler
	 * @return	\Jeht\Routing\RouteFactory
	 */
	public function patch(string $uri, $handler = null)
	{
		return $this->registerRoute(['PATCH'], $uri, $handler);
	}

	/**
	 * Register a PUT route.
	 *
	 * @param	string	$uri
	 * @param	string|array|\Closure	$handler
	 * @return	\Jeht\Routing\RouteFactory
	 */
	public function put(string $uri, $handler = null)
	{
		return $this->registerRoute(['PUT'], $uri, $handler);
	}

	/**
	 * Register an OPTIONS route.
	 *
	 * @param	string	$uri
	 * @param	string|array|\Closure	$handler
	 * @return	\Jeht\Routing\RouteFactory
	 */
	public function options(string $uri, $handler = null)
	{
		return $this->registerRoute(['OPTIONS'], $uri, $handler);
	}

	/**
	 * Register a DELETE route.
	 *
	 * @param	string	$uri
	 * @param	string|array|\Closure	$handler
	 * @return	\Jeht\Routing\RouteFactory
	 */
	public function delete(string $uri, $handler = null)
	{
		return $this->registerRoute(['DELETE'], $uri, $handler);
	}

	/**
	 * Register a route for any method.
	 *
	 * @param	string	$uri
	 * @param	string|array|\Closure	$handler
	 * @return	\Jeht\Routing\RouteFactory
	 */
	public function any(string $uri, $handler = null)
	{
		return $this->registerRoute(self::HTTP_METHODS, $uri, $handler);
	}

	/**
	 * Register a route for the given method(s).
	 *
	 * @param	string|array	$methods
	 * @param	string	$uri
	 * @param	string|array|\Closure	$handler
	 * @return	\Jeht\Routing\RouteFactory
	 * @throws	\InvalidArgumentException
	 * @throws	\Jeht\Exceptions\Http\InvalidHttpMethodException
	 */
	public function request($methods, string $uri, $handler)
	{
		if (!is_array($methods) && !is_string($methods)) {
			throw new InvalidArgumentException('$methods should be an array or string !');
		}
		//
		$methods = Arr::wrap($methods);
		//
		$upperMethods = array_map(function($item) {
			return strtoupper($item);
		}, $methods);
		//
		$valid = array_intersect($upperMethods, self::HTTP_METHODS);
		//
		if (count($valid) !== count($methods)) {
			throw new InvalidHttpMethodException(
				'One of the given HTTP methods is invalid: ' . implode(', ', $methods)
			);
		}
		//
		return $this->registerRoute($upperMethods, $uri, $handler);
	}

	/**
	 * Adds a name segment for the coming group.
	 *
	 * @param	string	$name
	 * @return	\Jeht\Routing\RouteGroup
	 */
	public function name(string $name)
	{
		return $this->routeGroup->name($name);
	}

	/**
	 * Adds a uri segment for the coming group.
	 *
	 * @param	string	$prefix
	 * @return	\Jeht\Routing\RouteGroup
	 */
	public function prefix(string $prefix)
	{
		return $this->routeGroup->prefix($prefix);
	}

	/**
	 * Adds a controller class for the coming group.
	 *
	 * @param	string	$controller
	 * @return	\Jeht\Routing\RouteGroup
	 */
	public function controller(string $controller)
	{
		return $this->routeGroup->controller($controller);
	}

	/**
	 * Defines the base namespace for the currently defined controller.
	 *
	 * @param	string	$namespace
	 * @return	\Jeht\Routing\RouteGroup
	 */
	public function namespace(string $namespace)
	{
		return $this->routeGroup->namespace($namespace);
	}

	/**
	 * Register the pending routes with the Router, cleaning the queue
	 * of pending ones.
	 *
	 * @return	void
	 */
	public function registerRoutes()
	{
		foreach ($this->routeFactories as $factory) {
			$this->router->registerRoute(
				$factory->fetch()
			);
		}
		//
		$this->routeFactories = [];
	}

	/**
	 * Register the pending routes with the Router, cleaning the queue
	 * of pending ones, and then retrieves the Router instance.
	 *
	 * @return	\Jeht\Routing\Router
	 */
	public function registerRoutesAndRetrieveRouter()
	{
		$this->registerRoutes();
		//
		return $this->router;
	}

}


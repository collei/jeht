<?php
namespace Ground\Http\Routing;

use Ground\Http\Routing\Router;
use Ground\Http\Routing\RouteFactory;
use Ground\Http\Routing\RouteGroup;
use Ground\Http\Servlets\HttpServlet;

class RouteRegistrar
{
	/**
	 * @var string[]
	 */
	protected const HTTP_METHODS = [
		'GET','POST','PUT','PATCH','OPTIONS','HEAD','DELETE'
	];

	/**
	 * @var \Ground\Http\Routing\Router
	 */
	protected $router = null;

	/**
	 * @var \Ground\Http\Routing\RouteGroup
	 */
	protected $routeGroup = null;

	protected $routeFactories = [];

	protected function registerRoute(array $methods, string $uri, $handler)
	{
		$this->routeFactories[] = $factory = new RouteFactory(
			$uri, $handler, $methods, $this->routeGroup
		);
		//
		return $factory;
	}

	public function __construct(Router $router)
	{
		$this->router = $router;
		$this->routeGroup = new RouteGroup();
	}

	public function get(string $uri, $handler = null)
	{
		return $this->registerRoute(['GET'], $uri, $handler);
	}

	public function head(string $uri, $handler = null)
	{
		return $this->registerRoute(['HEAD'], $uri, $handler);
	}

	public function post(string $uri, $handler = null)
	{
		return $this->registerRoute(['POST'], $uri, $handler);
	}

	public function patch(string $uri, $handler = null)
	{
		return $this->registerRoute(['PATCH'], $uri, $handler);
	}

	public function put(string $uri, $handler = null)
	{
		return $this->registerRoute(['PUT'], $uri, $handler);
	}

	public function options(string $uri, $handler = null)
	{
		return $this->registerRoute(['OPTIONS'], $uri, $handler);
	}

	public function delete(string $uri, $handler = null)
	{
		return $this->registerRoute(['DELETE'], $uri, $handler);
	}

	public function any(string $uri, $handler = null)
	{
		return $this->registerRoute(self::HTTP_METHODS, $uri, $handler);
	}

	public function request(array $methods, string $uri, $handler)
	{
		$upperMethods = array_map(function($item) {
			return strtoupper($item);
		}, $methods);

		$valid = array_intersect($upperMethods, self::HTTP_METHODS);

		if (count($valid) !== count($methods)) {
			throw new InvalidArgumentException(
				'One of the given HTTP methods is invalid: ' . implode(', ', $methods)
			);
		}

		return $this->registerRoute($upperMethods, $uri, $handler);
	}

	public function name(string $name)
	{
		return $this->routeGroup->name($name);
	}

	public function prefix(string $prefix)
	{
		return $this->routeGroup->prefix($prefix);
	}

	public function controller(string $controller)
	{
		return $this->routeGroup->controller($controller);
	}

	public function namespace(string $namespace)
	{
		return $this->routeGroup->namespace($namespace);
	}

	public function registerRoutes()
	{
		foreach ($this->routeFactories as $factory) {
			$this->router->registerRoute(
				$factory->fetch()
			);
		}
	}

	public function registerRoutesAndRetrieveRouter()
	{
		$this->registerRoutes();

		return $this->router;
	}

}



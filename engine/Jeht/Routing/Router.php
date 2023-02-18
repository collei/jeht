<?php
namespace Jeht\Routing;

use Jeht\Ground\Application;

class Router
{
	/**
	 * @var string[]
	 */
	protected const HTTP_METHODS = [
		'GET','POST','PUT','PATCH','OPTIONS','HEAD','DELETE'
	];

	/**
	 * @var array
	 */
	protected $routes = [];

	/**
	 * @var \Jeht\Ground\Application
	 */
	protected $app;

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
	 * @var array
	 */
	protected $middleware = [];

	/**
	 * @var array
	 */
	protected $middlewareGroups = [];

	/**
	 * @var \Jeht\Routing\Route
	 */
	protected $currentRoute = [];

	/**
	 * @var \Jeht\Http\Request
	 */
	protected $currentRequest = [];

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
	protected function addRoute(array $methods, string $uri, $handler)
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
	 */
	public function __construct(Application $app)
	{
		$this->app = $app;
		$routeGroup = new RouteGroup($app);
		$this->routeGroup = $routeGroup->setRouter($this);
		//
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
		return $this->addRoute(['GET'], $uri, $handler);
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
		return $this->addRoute(['HEAD'], $uri, $handler);
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
		return $this->addRoute(['POST'], $uri, $handler);
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
		return $this->addRoute(['PATCH'], $uri, $handler);
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
		return $this->addRoute(['PUT'], $uri, $handler);
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
		return $this->addRoute(['OPTIONS'], $uri, $handler);
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
		return $this->addRoute(['DELETE'], $uri, $handler);
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
		return $this->addRoute(self::HTTP_METHODS, $uri, $handler);
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
		return $this->addRoute($upperMethods, $uri, $handler);
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
	 * Groups all routes declared inside.
	 *
	 * @param	\Closure|string	$routes
	 * @return	void
	 */
	public function group($routes)
	{
du(__FILE__,__LINE__,__METHOD__,$routes);
		if ($routes instanceof Closure) {
			$this->routeGroup->group($routes);
		} else {
			$this->routeGroup->group(function() use ($routes){

du(__FILE__,__LINE__,__METHOD__,$routes);

				(new RouteFileRegistrar($this))->register($routes);
			});
		}
	}

	/**
	 * Load the provided routes.
	 *
	 * @param  \Closure|string  $routes
	 * @return void
	 */
	protected function loadRoutes($routes)
	{
		if ($routes instanceof Closure) {
			$routes($this);
		} else {
			(new RouteFileRegistrar($this))->register($routes);
		}
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
			$this->registerRoute(
				$factory->fetch()->setContainer($this->app)->setRouter($this)
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

	/**
	 * Tests the given $requestUri against $regex.
	 * Returns an array with two elements: the boolean result of the match
	 * and an associative array of parameters which may be empty.
	 *
	 * @param string $requestUri
	 * @param string $regex
	 * @return [bool, array]
	 */
	public static function requestMatchesRegex(
		string $requestUri, string $regex
	) {
		$bool = (1 === preg_match($regex, $requestUri, $matches));
		$parameters = [];
		//
		if ($bool && !empty($matches)) {
			foreach ($matches as $key => $value) {
				if (is_string($key)) {
					$parameters[$key] = $value;
				}
			}
		}
		//
		return [$bool, $parameters];
	}

	public function registerRoute(Route $route)
	{
		$this->routes[] = $route;
	}

	public function dispatch($request)
	{
		foreach ($this->routes as $route) {
			if ($route->matches($request)) {
				return $route->runRoute($request);
			}
		}
		//
		return [
			'status' => 404,
			'errors' => new \Exception('Route not found for request URI: ' . $request->getUri())
		];
	}


	/**
	 * Get all of the defined middleware short-hand names.
	 *
	 * @return array
	 */
	public function getMiddleware()
	{
		return $this->middleware;
	}

	/**
	 * Register a short-hand name for a middleware.
	 *
	 * @param  string  $name
	 * @param  string  $class
	 * @return $this
	 */
	public function aliasMiddleware($name, $class)
	{
		$this->middleware[$name] = $class;

		return $this;
	}

	/**
	 * Check if a middlewareGroup with the given name exists.
	 *
	 * @param  string  $name
	 * @return bool
	 */
	public function hasMiddlewareGroup($name)
	{
		return array_key_exists($name, $this->middlewareGroups);
	}

	/**
	 * Get all of the defined middleware groups.
	 *
	 * @return array
	 */
	public function getMiddlewareGroups()
	{
		return $this->middlewareGroups;
	}

	/**
	 * Register a group of middleware.
	 *
	 * @param  string  $name
	 * @param  array  $middleware
	 * @return $this
	 */
	public function middlewareGroup($name, array $middleware)
	{
		$this->middlewareGroups[$name] = $middleware;

		return $this;
	}

	/**
	 * Add a middleware to the beginning of a middleware group.
	 *
	 * If the middleware is already in the group, it will not be added again.
	 *
	 * @param  string  $group
	 * @param  string  $middleware
	 * @return $this
	 */
	public function prependMiddlewareToGroup($group, $middleware)
	{
		if (isset($this->middlewareGroups[$group]) && ! in_array($middleware, $this->middlewareGroups[$group])) {
			array_unshift($this->middlewareGroups[$group], $middleware);
		}

		return $this;
	}

	/**
	 * Add a middleware to the end of a middleware group.
	 *
	 * If the middleware is already in the group, it will not be added again.
	 *
	 * @param  string  $group
	 * @param  string  $middleware
	 * @return $this
	 */
	public function pushMiddlewareToGroup($group, $middleware)
	{
		if (! array_key_exists($group, $this->middlewareGroups)) {
			$this->middlewareGroups[$group] = [];
		}

		if (! in_array($middleware, $this->middlewareGroups[$group])) {
			$this->middlewareGroups[$group][] = $middleware;
		}

		return $this;
	}

	/**
	 * Flush the router's middleware groups.
	 *
	 * @return $this
	 */
	public function flushMiddlewareGroups()
	{
		$this->middlewareGroups = [];

		return $this;
	}




}


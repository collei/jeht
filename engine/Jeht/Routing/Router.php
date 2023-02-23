<?php
namespace Jeht\Routing;

use Jeht\Ground\Application;
use Jeht\Collections\Collection;
use Jeht\Interfaces\Routing\RouteInterface;
use Jeht\Interfaces\Routing\RouterInterface;
use Jeht\Http\Request;
use Jeht\Http\ResponsePreparator;
use Closure;
use ReflectionClass;

class Router implements RouterInterface
{
	/**
	 * @var \Jeht\Routing\RouteCollection
	 */
	protected $routes;

	/**
	 * @var \Jeht\Ground\Application
	 */
	protected $container;

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
	 * @var \Jeht\Interfaces\Routing\RouteInterface
	 */
	protected $currentRoute = [];

	/**
	 * @var \Jeht\Http\Request
	 */
	protected $currentRequest = [];

	/**
	 * Aggregate uri and name segments, occasionally applying the action
	 * if specified.
	 *
	 * @param	string	$withUriSuffix
	 * @param	string	$withName
	 * @param	mixed	$withAction
	 * @return	array
	 */
	protected function aggregateAttributes(string $withUriSuffix, $withAction, string $withName = null)
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
			? ($current['name'] . ($withName ? ('.'.$withName) : ''))
			: ($withName ?? '');
		//
		$action = $withAction ?? $current['action'] ?? null;
		//
		return array($uri, $name, $action);
	}

	/**
	 * Register a route
	 *
	 * @param	string|array	$methods
	 * @param	string	$uri
	 * @param	mixed	$action
	 * @return	\Jeht\Routing\Routefactory
	 */
	protected function addRoute($methods, string $uri, $action)
	{
		// Aggregate path prefixes into a uri with the current 'suffix'
		// and also provides convenient method of override the action
		// of the current group.
		[$uri, $name, $action] = $this->aggregateAttributes($uri, $action);
		//
		$this->routeFactories[] = $factory = RouteFactory::for(
			$methods, $this->appBaseUri.$uri, $action, $name
		);
		//
		return $factory;
	}

	/**
	 * Initializes the route registrar
	 *
	 * @param	\Jeht\Ground\Application	$container
	 */
	public function __construct(Application $container)
	{
		$this->container = $container;
		$this->routeGroup = (new RouteGroup($container))->setRouter($this);
		$this->routes = new RouteCollection;
		//
		$this->container->instance(RouteGroup::class, $this->routeGroup);
		$this->container->instance(RouteCollection::class, $this->routes);
		//
		$this->appBaseUri = $this->container['app.rooturi'];
	}

	/**
	 * Return the underlying Route collection
	 *
	 * @return \Jeht\Http\RouteCollection
	 */	
	public function getRoutes()
	{
		return $this->routes;
	}

	/**
	 * Register a GET route.
	 *
	 * @param	string	$uri
	 * @param	string|array|\Closure	$action
	 * @return	\Jeht\Routing\RouteFactory
	 */
	public function get(string $uri, $action = null)
	{
		return $this->addRoute(['GET'], $uri, $action);
	}

	/**
	 * Register a HEAD route.
	 *
	 * @param	string	$uri
	 * @param	string|array|\Closure	$action
	 * @return	\Jeht\Routing\RouteFactory
	 */
	public function head(string $uri, $action = null)
	{
		return $this->addRoute(['HEAD'], $uri, $action);
	}

	/**
	 * Register a POST route.
	 *
	 * @param	string	$uri
	 * @param	string|array|\Closure	$action
	 * @return	\Jeht\Routing\RouteFactory
	 */
	public function post(string $uri, $action = null)
	{
		return $this->addRoute(['POST'], $uri, $action);
	}

	/**
	 * Register a PATCH route.
	 *
	 * @param	string	$uri
	 * @param	string|array|\Closure	$action
	 * @return	\Jeht\Routing\RouteFactory
	 */
	public function patch(string $uri, $action = null)
	{
		return $this->addRoute(['PATCH'], $uri, $action);
	}

	/**
	 * Register a PUT route.
	 *
	 * @param	string	$uri
	 * @param	string|array|\Closure	$action
	 * @return	\Jeht\Routing\RouteFactory
	 */
	public function put(string $uri, $action = null)
	{
		return $this->addRoute(['PUT'], $uri, $action);
	}

	/**
	 * Register an OPTIONS route.
	 *
	 * @param	string	$uri
	 * @param	string|array|\Closure	$action
	 * @return	\Jeht\Routing\RouteFactory
	 */
	public function options(string $uri, $action = null)
	{
		return $this->addRoute(['OPTIONS'], $uri, $action);
	}

	/**
	 * Register a DELETE route.
	 *
	 * @param	string	$uri
	 * @param	string|array|\Closure	$action
	 * @return	\Jeht\Routing\RouteFactory
	 */
	public function delete(string $uri, $action = null)
	{
		return $this->addRoute(['DELETE'], $uri, $action);
	}

	/**
	 * Register a route for any method.
	 *
	 * @param	string	$uri
	 * @param	string|array|\Closure	$action
	 * @return	\Jeht\Routing\RouteFactory
	 */
	public function any(string $uri, $action = null)
	{
		return $this->addRoute(self::HTTP_METHODS, $uri, $action);
	}

	/**
	 * Register a route for the given method(s).
	 *
	 * @param	string|array	$methods
	 * @param	string	$uri
	 * @param	string|array|\Closure	$action
	 * @return	\Jeht\Routing\RouteFactory
	 * @throws	\InvalidArgumentException
	 * @throws	\Jeht\Exceptions\Http\InvalidHttpMethodException
	 */
	public function request($methods, string $uri, $action)
	{
		return $this->addRoute($methods, $uri, $action);
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
	 * Adds an action class for the coming group.
	 *
	 * @param	string	$controller
	 * @return	\Jeht\Routing\RouteGroup
	 */
	public function controller(string $controller)
	{
		return $this->routeGroup->action($controller);
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
	 * Defines the middleware to be included for the group.
	 *
	 * @param	string|array	$middleware
	 * @return	\Jeht\Routing\RouteGroup
	 */
	public function middleware(string $namespace)
	{
		return $this->routeGroup->middleware($namespace);
	}

	/**
	 * Defines the middleware to be excluded from the group.
	 *
	 * @param	string|array	$middleware
	 * @return	\Jeht\Routing\RouteGroup
	 */
	public function withoutMiddleware(string $namespace)
	{
		return $this->routeGroup->withoutMiddleware($namespace);
	}

	/**
	 * Groups all routes declared inside.
	 *
	 * @param	\Closure|string	$routes
	 * @return	void
	 */
	public function group($routes)
	{
		if ($routes instanceof Closure) {
			$this->routeGroup->group($routes);
		} else {
			(new RouteFileRegistrar($this))->register($routes);
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
				$factory->fetch()->setContainer($this->container)->setRouter($this)
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
	 *
	 * @param string $requestUri
	 * @param string $regex
	 * @return bool
	 */
	public function requestMatchesRegex(string $requestUri, string $regex)
	{
		return (1 === preg_match($regex, $requestUri, $teste));
	}

	/**
	 * Returns an associative array of parameters (which may be empty).
	 *
	 * @param string $requestUri
	 * @param string $regex
	 * @return array
	 */
	public function fetchParameterValuesFromUri(string $requestUri, string $regex)
	{
		$result = [];
		//
		if (1 === preg_match($regex, $requestUri, $matches)) {
			if (is_array($matches)) {
				foreach ($matches as $key => $value) {
					if (is_string($key)) {
						$result[$key] = $value;
					}
				}
			}
		}
		//
		return $result;
	}

	/**
	 * Register the route witht the router.
	 *
	 * @param \Jeht\Interfaces\Routing\RouteInterface
	 */
	public function registerRoute(RouteInterface $route)
	{
		$this->routes->add($route);
	}

	/**
	 * Dispatches the request to a matching route, if any.
	 *
	 * @param \Jeht\Http\Request $request
	 * @return 
	 */
	public function dispatch($request)
	{
		if ($route = $this->routes->match($request)) {
			return $this->runRoute($route, $request);
		}
		//
		throw new NotFoundHttpException(
			'No Route could match for the uri [' . $request->getUri() . '] and no fallback was found.'
		);
	}

	/**
	 * Run the specified $route for the given $request
	 *
	 * @param \Jeht\Interfaces\Routing\RouteInterface
	 * @param \Jeht\Http\Request
	 */
	protected function runRoute(RouteInterface $route, Request $request)
	{
		/**
		 * @todo 
		$request->setRouteResolver(function() use ($route){
			return $route;
		});
		 */

		/**
		 * @todo
		$this->events->dispatch(new RouteMatched($route, $request));
		 */

		return $this->prepareResponse(
			$request,
			$this->runRouteWithinStack($route, $request)
		);
	}

	/**
	 * Run the given route within a Stack "onion" instance.
	 *
	 * @param  \Illuminate\Routing\Route  $route
	 * @param  \Illuminate\Http\Request  $request
	 * @return mixed
	 */
	protected function runRouteWithinStack(RouteInterface $route, Request $request)
	{
		$shouldSkipMiddleware = $this->container->bound('middleware.disable') &&
								$this->container->make('middleware.disable') === true;

		$middleware = $shouldSkipMiddleware ? [] : $this->gatherRouteMiddleware($route);

		return (new Pipeline($this->container))
						->send($request)
						->through($middleware)
						->then(function ($request) use ($route) {
							return $this->prepareResponse(
								$request, $route->run()
							);
						});
	}

	/**
	 * Gather the middleware for the given route with resolved class names.
	 *
	 * @param  \Jeht\Interfaces\Routing\RouteInterface  $route
	 * @return array
	 */
	public function gatherRouteMiddleware(RouteInterface $route)
	{
		return $this->resolveMiddleware($route->gatherMiddleware(), $route->excludedMiddleware());
	}

	/**
	 * Resolve a flat array of middleware classes from the provided array.
	 *
	 * @param  array  $middleware
	 * @param  array  $excluded
	 * @return array
	 */
	public function resolveMiddleware(array $middleware, array $excluded = [])
	{
		$excluded = Collection::for($excluded)->map(function ($name) {
			return (array) MiddlewareNameResolver::resolve(
				$name, $this->middleware, $this->middlewareGroups
			);
		})->flatten()->values()->all();

		$middleware = Collection::for($middleware)->map(function ($name) {
			return (array) MiddlewareNameResolver::resolve(
				$name, $this->middleware, $this->middlewareGroups
			);
		})->flatten()->reject(function ($name) use ($excluded) {
			if (empty($excluded)) {
				return false;
			}

			if ($name instanceof Closure) {
				return false;
			}

			if (in_array($name, $excluded, true)) {
				return true;
			}

			if (! class_exists($name)) {
				return false;
			}

			$reflection = new ReflectionClass($name);

			return Collection::for($excluded)->contains(
				function($exclude) {
					return class_exists($exclude) && $reflection->isSubclassOf($exclude);
				}
			);
		})->values();

		return $this->sortMiddleware($middleware);
	}

	/**
	 * Sort the given middleware by priority.
	 *
	 * @param  \Jeht\Collections\Collection  $middlewares
	 * @return array
	 */
	protected function sortMiddleware(Collection $middlewares)
	{
		return (new SortedMiddleware($this->middlewarePriority, $middlewares))->all();
	}

	/**
	 * Remove any duplicate middleware from the given array.
	 *
	 * @param  array  $middleware
	 * @return array
	 */
	public static function uniqueMiddleware(array $middleware)
	{
		$seen = [];
		$result = [];

		foreach ($middleware as $value) {
			$key = is_object($value) ? spl_object_id($value) : $value;

			if (! isset($seen[$key])) {
				$seen[$key] = true;
				$result[] = $value;
			}
		}

		return $result;
	}

	/**
	 * Runs the response preparator with the given response.
	 *
	 * @param \Jeht\Http\Request
	 * @param mixed $response
	 * @return \Jeht\Http\Response
	 */
	protected function prepareResponse(Request $request, $response)
	{
		return (new ResponsePreparator)->prepare($request, $response);
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


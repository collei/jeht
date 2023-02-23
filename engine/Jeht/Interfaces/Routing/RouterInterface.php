<?php
namespace Jeht\Interfaces\Routing;

use Jeht\Ground\Application;
use Jeht\Collections\Collection;
use Jeht\Http\Request;
use Jeht\Http\ResponsePreparator;
use Closure;
use ReflectionClass;

interface RouterInterface
{
	/**
	 * Initializes the route registrar
	 *
	 * @param	\Jeht\Ground\Application	$container
	 */
	public function __construct(Application $container);

	/**
	 * Return the underlying Route collection
	 *
	 * @return \Jeht\Http\RouteCollection
	 */	
	public function getRoutes();

	/**
	 * Register a GET route.
	 *
	 * @param	string	$uri
	 * @param	string|array|\Closure	$action
	 * @return	\Jeht\Routing\RouteFactory
	 */
	public function get(string $uri, $action = null);

	/**
	 * Register a HEAD route.
	 *
	 * @param	string	$uri
	 * @param	string|array|\Closure	$action
	 * @return	\Jeht\Routing\RouteFactory
	 */
	public function head(string $uri, $action = null);

	/**
	 * Register a POST route.
	 *
	 * @param	string	$uri
	 * @param	string|array|\Closure	$action
	 * @return	\Jeht\Routing\RouteFactory
	 */
	public function post(string $uri, $action = null);

	/**
	 * Register a PATCH route.
	 *
	 * @param	string	$uri
	 * @param	string|array|\Closure	$action
	 * @return	\Jeht\Routing\RouteFactory
	 */
	public function patch(string $uri, $action = null);

	/**
	 * Register a PUT route.
	 *
	 * @param	string	$uri
	 * @param	string|array|\Closure	$action
	 * @return	\Jeht\Routing\RouteFactory
	 */
	public function put(string $uri, $action = null);

	/**
	 * Register an OPTIONS route.
	 *
	 * @param	string	$uri
	 * @param	string|array|\Closure	$action
	 * @return	\Jeht\Routing\RouteFactory
	 */
	public function options(string $uri, $action = null);

	/**
	 * Register a DELETE route.
	 *
	 * @param	string	$uri
	 * @param	string|array|\Closure	$action
	 * @return	\Jeht\Routing\RouteFactory
	 */
	public function delete(string $uri, $action = null);

	/**
	 * Register a route for any method.
	 *
	 * @param	string	$uri
	 * @param	string|array|\Closure	$action
	 * @return	\Jeht\Routing\RouteFactory
	 */
	public function any(string $uri, $action = null);

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
	public function request($methods, string $uri, $action);

	/**
	 * Adds a name segment for the coming group.
	 *
	 * @param	string	$name
	 * @return	\Jeht\Routing\RouteGroup
	 */
	public function name(string $name);

	/**
	 * Adds a uri segment for the coming group.
	 *
	 * @param	string	$prefix
	 * @return	\Jeht\Routing\RouteGroup
	 */
	public function prefix(string $prefix);

	/**
	 * Adds an action class for the coming group.
	 *
	 * @param	string	$controller
	 * @return	\Jeht\Routing\RouteGroup
	 */
	public function controller(string $controller);

	/**
	 * Defines the base namespace for the currently defined controller.
	 *
	 * @param	string	$namespace
	 * @return	\Jeht\Routing\RouteGroup
	 */
	public function namespace(string $namespace);

	/**
	 * Defines the middleware to be included for the group.
	 *
	 * @param	string|array	$middleware
	 * @return	\Jeht\Routing\RouteGroup
	 */
	public function middleware(string $namespace);

	/**
	 * Defines the middleware to be excluded from the group.
	 *
	 * @param	string|array	$middleware
	 * @return	\Jeht\Routing\RouteGroup
	 */
	public function withoutMiddleware(string $namespace);

	/**
	 * Groups all routes declared inside.
	 *
	 * @param	\Closure|string	$routes
	 * @return	void
	 */
	public function group($routes);

	/**
	 * Register the pending routes with the Router, cleaning the queue
	 * of pending ones.
	 *
	 * @return	void
	 */
	public function registerRoutes();

	/**
	 * Register the pending routes with the Router, cleaning the queue
	 * of pending ones, and then retrieves the Router instance.
	 *
	 * @return	\Jeht\Routing\Router
	 */
	public function registerRoutesAndRetrieveRouter();

	/**
	 * Tests the given $requestUri against $regex.
	 *
	 * @param string $requestUri
	 * @param string $regex
	 * @return bool
	 */
	public function requestMatchesRegex(string $requestUri, string $regex);

	/**
	 * Returns an associative array of parameters (which may be empty).
	 *
	 * @param string $requestUri
	 * @param string $regex
	 * @return array
	 */
	public function fetchParameterValuesFromUri(string $requestUri, string $regex);

	/**
	 * Register the route witht the router.
	 *
	 * @param \Jeht\Interfaces\Routing\RouteInterface
	 * @return void
	 */
	public function registerRoute(RouteInterface $route);

	/**
	 * Dispatches the request to a matching route, if any.
	 *
	 * @param \Jeht\Http\Request $request
	 * @return 
	 */
	public function dispatch($request);

	/**
	 * Gather the middleware for the given route with resolved class names.
	 *
	 * @param  \Jeht\Interfaces\Routing\RouteInterface  $route
	 * @return array
	 */
	public function gatherRouteMiddleware(RouteInterface $route);

	/**
	 * Resolve a flat array of middleware classes from the provided array.
	 *
	 * @param  array  $middleware
	 * @param  array  $excluded
	 * @return array
	 */
	public function resolveMiddleware(array $middleware, array $excluded = []);

	/**
	 * Get all of the defined middleware short-hand names.
	 *
	 * @return array
	 */
	public function getMiddleware();

	/**
	 * Register a short-hand name for a middleware.
	 *
	 * @param  string  $name
	 * @param  string  $class
	 * @return $this
	 */
	public function aliasMiddleware($name, $class);

	/**
	 * Check if a middlewareGroup with the given name exists.
	 *
	 * @param  string  $name
	 * @return bool
	 */
	public function hasMiddlewareGroup($name);

	/**
	 * Get all of the defined middleware groups.
	 *
	 * @return array
	 */
	public function getMiddlewareGroups();

	/**
	 * Register a group of middleware.
	 *
	 * @param  string  $name
	 * @param  array  $middleware
	 * @return $this
	 */
	public function middlewareGroup($name, array $middleware);

	/**
	 * Add a middleware to the beginning of a middleware group.
	 *
	 * If the middleware is already in the group, it will not be added again.
	 *
	 * @param  string  $group
	 * @param  string  $middleware
	 * @return $this
	 */
	public function prependMiddlewareToGroup($group, $middleware);

	/**
	 * Add a middleware to the end of a middleware group.
	 *
	 * If the middleware is already in the group, it will not be added again.
	 *
	 * @param  string  $group
	 * @param  string  $middleware
	 * @return $this
	 */
	public function pushMiddlewareToGroup($group, $middleware);

	/**
	 * Flush the router's middleware groups.
	 *
	 * @return $this
	 */
	public function flushMiddlewareGroups();

}


<?php
namespace Jeht\Routing;

use Countable;
use IteratorAggregate;
use ArrayIterator;
use Jeht\Interfaces\Routing\RouteCollectionInterface;
use Jeht\Routing\Route;
use Jeht\Http\Request;
use Jeht\Http\HttpMethods;
use Jeht\Container\Container;
use Jeht\Support\Arr;
use Jeht\Collections\Collection;
use Jeht\Exceptions\Http\NotFoundHttpException;
use Jeht\Exceptions\Http\MethodNotAllowedHttpException;


/**
 * Adapted from Laravel's Illuminate\Routing\RouteCollection
 * with methods from Laravel's Illuminate\Routing\AbstractRouteCollection
 *
 */
class RouteCollection implements Countable, IteratorAggregate, RouteCollectionInterface
{
	/**
	 * An array of the routes keyed by method.
	 *
	 * @var array
	 */
	protected $verbs = [];

	/**
	 * An array of the routes keyed by method.
	 *
	 * @var array
	 */
	protected $routes = [];

	/**
	 * A flattened array of all of the routes.
	 *
	 * @var \Jeht\Routing\Route[]
	 */
	protected $allRoutes = [];

	/**
	 * A look-up table of routes by their names.
	 *
	 * @var \Jeht\Routing\Route[]
	 */
	protected $nameList = [];

	/**
	 * A look-up table of routes by controller action.
	 *
	 * @var \Jeht\Routing\Route[]
	 */
	protected $actionList = [];

	/**
	 * Add a Route instance to the collection.
	 *
	 * @param  \Jeht\Routing\Route  $route
	 * @return \Jeht\Routing\Route
	 */
	public function add(Route $route)
	{
		$this->addToCollections($route);
		$this->addLookups($route);
		//
		return $route;
	}

	/**
	 * Add the given route to the arrays of routes.
	 *
	 * @param  \Jeht\Routing\Route  $route
	 * @return void
	 */
	protected function addToCollections($route)
	{
		$domainAndUri = $route->getDomain().$route->uri();

		foreach ($route->methods() as $method) {
			$this->routes[$method][$domainAndUri] = $route;
		}

		$this->allRoutes[$method.$domainAndUri] = $route;
	}

	/**
	 * Add the route to any look-up tables if necessary.
	 *
	 * @param  \Jeht\Routing\Route  $route
	 * @return void
	 */
	protected function addLookups($route)
	{
		// If the route has a name, we will add it to the name look-up table so that we
		// will quickly be able to find any route associate with a name and not have
		// to iterate through every route every time we need to perform a look-up.
		if ($name = $route->getName()) {
			$this->nameList[$name] = $route;
		}

		// When the route is routing to a controller we will also store the action that
		// is used by the route. This will let us reverse route to controllers while
		// processing a request and easily generate URLs to the given controllers.
		$action = $route->getAction();

		if (isset($action['controller'])) {
			$this->addToActionList($action, $route);
		}
	}

	/**
	 * Add a route to the controller action dictionary.
	 *
	 * @param  array  $action
	 * @param  \Jeht\Routing\Route  $route
	 * @return void
	 */
	protected function addToActionList($action, $route)
	{
		$this->actionList[trim($action['controller'], '\\')] = $route;
	}

	/**
	 * Refresh the name look-up table.
	 *
	 * This is done in case any names are fluently defined or if routes are overwritten.
	 *
	 * @return void
	 */
	public function refreshNameLookups()
	{
		$this->nameList = [];

		foreach ($this->allRoutes as $route) {
			if ($route->getName()) {
				$this->nameList[$route->getName()] = $route;
			}
		}
	}

	/**
	 * Refresh the action look-up table.
	 *
	 * This is done in case any actions are overwritten with new controllers.
	 *
	 * @return void
	 */
	public function refreshActionLookups()
	{
		$this->actionList = [];

		foreach ($this->allRoutes as $route) {
			if (isset($route->getAction()['controller'])) {
				$this->addToActionList($route->getAction(), $route);
			}
		}
	}

	/**
	 * Find the first route matching a given request.
	 *
	 * @param  \Jeht\Http\Request  $request
	 * @return \Jeht\Routing\Route
	 *
	 * @throws \Jeht\Exceptions\Http\MethodNotAllowedHttpException
	 * @throws \Jeht\Exceptions\Http\NotFoundHttpException
	 */
	public function match(Request $request)
	{
		$routes = $this->get($request->getMethod());

		// First, we will see if we can find a matching route for this current request
		// method. If we can, great, we can just return it so that it can be called
		// by the consumer. Otherwise we will check for routes with another verb.
		$route = $this->matchAgainstRoutes($routes, $request);

		return $this->handleMatchedRoute($request, $route);
	}

	/**
	 * Handle the matched route.
	 *
	 * @param  \Jeht\Http\Request  $request
	 * @param  \Jeht\Routing\Route|null  $route
	 * @return \Jeht\Routing\Route
	 *
	 * @throws \Jeht\Exceptions\Http\NotFoundHttpException
	 */
	protected function handleMatchedRoute(Request $request, $route)
	{
		if (! is_null($route)) {
			return $route->bind($request);
		}

		// If no route was found we will now check if a matching route is specified by
		// another HTTP verb. If it is we will need to throw a MethodNotAllowed and
		// inform the user agent of which HTTP verb it should use for this route.
		$others = $this->checkForAlternateVerbs($request);

		if (count($others) > 0) {
			return $this->getRouteForMethods($request, $others);
		}

		throw new NotFoundHttpException;
	}

	/**
	 * Determine if any routes match on another HTTP verb.
	 *
	 * @param  \Jeht\Http\Request  $request
	 * @return array
	 */
	protected function checkForAlternateVerbs($request)
	{
		$methods = array_diff(HttpMethods::HTTP_METHODS, [$request->getMethod()]);

		// Here we will spin through all verbs except for the current request verb and
		// check to see if any routes respond to them. If they do, we will return a
		// proper error response with the correct headers on the response string.
		return array_values(array_filter(
			$methods,
			function ($method) use ($request) {
				return ! is_null($this->matchAgainstRoutes($this->get($method), $request, false));
			}
		));
	}

	/**
	 * Determine if a route in the array matches the request.
	 *
	 * @param  \Jeht\Routing\Route[]  $routes
	 * @param  \Jeht\Http\Request  $request
	 * @param  bool  $includingMethod
	 * @return \Jeht\Routing\Route|null
	 */
	protected function matchAgainstRoutes(array $routes, $request, $includingMethod = true)
	{
		[$fallbacks, $routes] = Collection::for($routes)->partition(function ($route) {
			return $route->isFallback();
		});

		return $routes->merge($fallbacks)->first(function (Route $route) use ($request, $includingMethod) {
			return $route->matches($request, $includingMethod); // ? $route : null;
		});
	}

	/**
	 * Get a route (if necessary) that responds when other available methods are present.
	 *
	 * @param  \Jeht\Http\Request  $request
	 * @param  string[]  $methods
	 * @return \Jeht\Routing\Route
	 *
	 * @throws \Jeht\Exceptions\Http\MethodNotAllowedHttpException
	 */
	protected function getRouteForMethods($request, array $methods)
	{
		if ($request->method() === 'OPTIONS') {
			return (new Route('OPTIONS', $request->path(), function () use ($methods) {
				return new Response('', 200, ['Allow' => implode(',', $methods)]);
			}))->bind($request);
		}

		$this->methodNotAllowed($methods, $request->method());
	}

	/**
	 * Throw a method not allowed HTTP exception.
	 *
	 * @param  array  $others
	 * @param  string  $method
	 * @return void
	 *
	 * @throws \Jeht\Exceptions\Http\MethodNotAllowedHttpException
	 */
	protected function methodNotAllowed(array $others, $method)
	{
		$message = 'The ['.$method.'] method is not supported for this route.'
			. ' Supported methods: ['.implode(', ', $others).'].';
		//
		throw new MethodNotAllowedHttpException($others, $message);
	}

	/**
	 * Compile the routes for caching.
	 *
	 * @return array
	 */
	public function compile()
	{
		return [];
	}

	/**
	 * Get a randomly generated route name.
	 *
	 * @return string
	 */
	protected function generateRouteName()
	{
		return 'generated::'.Str::random(32);
	}

	/**
	 * Get an iterator for the items.
	 *
	 * @return \ArrayIterator
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->getRoutes());
	}

	/**
	 * Count the number of items in the collection.
	 *
	 * @return int
	 */
	public function count()
	{
		return count($this->getRoutes());
	}

	/**
	 * Get routes from the collection by method.
	 *
	 * @param  string|null  $method
	 * @return \Jeht\Routing\Route[]
	 */
	public function get($method = null)
	{
		return is_null($method) ? $this->getRoutes() : Arr::get($this->routes, $method, []);
	}

	/**
	 * Determine if the route collection contains a given named route.
	 *
	 * @param  string  $name
	 * @return bool
	 */
	public function hasNamedRoute($name)
	{
		return ! is_null($this->getByName($name));
	}

	/**
	 * Get a route instance by its name.
	 *
	 * @param  string  $name
	 * @return \Jeht\Routing\Route|null
	 */
	public function getByName($name)
	{
		return $this->nameList[$name] ?? null;
	}

	/**
	 * Get a route instance by its controller action.
	 *
	 * @param  string  $action
	 * @return \Jeht\Routing\Route|null
	 */
	public function getByAction($action)
	{
		return $this->actionList[$action] ?? null;
	}

	/**
	 * Get all of the routes in the collection.
	 *
	 * @return \Jeht\Routing\Route[]
	 */
	public function getRoutes()
	{
		return array_values($this->allRoutes);
	}

	/**
	 * Get all of the routes keyed by their HTTP verb / method.
	 *
	 * @return array
	 */
	public function getRoutesByMethod()
	{
		return $this->routes;
	}

	/**
	 * Get all of the routes keyed by their name.
	 *
	 * @return \Jeht\Routing\Route[]
	 */
	public function getRoutesByName()
	{
		return $this->nameList;
	}

	/**
	 * Convert the collection to a CompiledRouteCollection instance.
	 *
	 * @param  \Jeht\Routing\Router  $router
	 * @param  \Jeht\Container\Container  $container
	 * @return \Jeht\Routing\CompiledRouteCollection
	 */
	public function toCompiledRouteCollection(Router $router, Container $container)
	{
		['compiled' => $compiled, 'attributes' => $attributes] = $this->compile();

		return (new CompiledRouteCollection($compiled, $attributes))
			->setRouter($router)
			->setContainer($container);
	}
}
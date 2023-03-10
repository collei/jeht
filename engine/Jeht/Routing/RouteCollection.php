<?php
namespace Jeht\Routing;

use Countable;
use IteratorAggregate;
use ArrayIterator;
use Jeht\Routing\Interfaces\RouteCollectionInterface;
use Jeht\Routing\Interfaces\RouteInterface;
use Jeht\Http\Request;
use Jeht\Http\HttpMethods;
use Jeht\Container\Container;
use Jeht\Support\Arr;
use Jeht\Collections\Collection;
use Jeht\Http\Exceptions\NotFoundHttpException;
use Jeht\Http\Exceptions\MethodNotAllowedHttpException;


/**
 * Adapted from Laravel's Illuminate\Routing\RouteCollection
 * with methods from Laravel's Illuminate\Routing\AbstractRouteCollection
 *
 */
class RouteCollection extends AbstractRouteCollection implements Countable, IteratorAggregate, RouteCollectionInterface
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
	 * @param  \Jeht\Routing\Interfaces\RouteInterface  $route
	 * @return \Jeht\Routing\Interfaces\RouteInterface
	 */
	public function add(RouteInterface $route)
	{
		$this->addToCollections($route);
		$this->addLookups($route);
		//
		return $route;
	}

	/**
	 * Add the given route to the arrays of routes.
	 *
	 * @param  \Jeht\Routing\Interfaces\RouteInterface  $route
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
	 * @param  \Jeht\Routing\Interfaces\RouteInterface  $route
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
	 * @param  \Jeht\Routing\Interfaces\RouteInterface  $route
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
	 * @return \Jeht\Routing\Interfaces\RouteInterface
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
	 * Compile the routes and returns them as array.
	 *
	 * @return array
	 */
	protected function getCompiledRoutesAsArray()
	{
		$compiled = [];
		//
		foreach ($this->getRoutes() as $route) {
			$compiled[] = $route->compile();
		}
		//
		return $compiled;
	}

	/**
	 * Compile the routes for caching.
	 *
	 * @return array
	 */
	public function compile()
	{
		$compiled = $this->getCompiledRoutesAsArray();

		$attributes = [];

		foreach ($this->getRoutes() as $route) {
			$attributes[$route->getName()] = [
				'methods' => $route->methods(),
				'uri' => $route->uri(),
				'action' => $route->getAction(),
				'fallback' => $route->isFallback,
			];
		}

		return compact('compiled', 'attributes');
	}

	/**
	 * Get routes from the collection by method.
	 *
	 * @param  string|null  $method
	 * @return \Jeht\Routing\Interfaces\RouteInterface[]
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
	 * @return \Jeht\Routing\Interfaces\RouteInterface|null
	 */
	public function getByName($name)
	{
		return $this->nameList[$name] ?? null;
	}

	/**
	 * Get a route instance by its controller action.
	 *
	 * @param  string  $action
	 * @return \Jeht\Routing\Interfaces\RouteInterface|null
	 */
	public function getByAction($action)
	{
		return $this->actionList[$action] ?? null;
	}

	/**
	 * Get all of the routes in the collection.
	 *
	 * @return \Jeht\Routing\Interfaces\RouteInterface[]
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
	 * @return \Jeht\Routing\Interfaces\RouteInterface[]
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

	/**
	 * Create a new RouteCollection instance from an existing
	 * CompiledRouteCollection instance.
	 *
	 * @param  \Jeht\Routing\CompiledRouteCollection  $routes
	 * @return static
	 */
	public static function createFromCompiled(CompiledRouteCollection $routes)
	{
		$self = new static;
		//
		foreach ($routes as $route) {
			$self->add(Route::fromCompiledRoute($route));
		}
		//
		return $self;
	}

}

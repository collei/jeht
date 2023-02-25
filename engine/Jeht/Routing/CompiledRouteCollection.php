<?php
namespace Jeht\Routing;

use Countable;
use Serializable;
use Closure;
use IteratorAggregate;
use ArrayIterator;
use Jeht\Interfaces\Routing\RouteInterface;
use Jeht\Interfaces\Routing\RouteCollectionInterface;
use Jeht\Http\Request;
use Jeht\Http\HttpMethods;
use Jeht\Container\Container;
use Jeht\Support\Arr;
use Jeht\Collections\Collection;
use Jeht\Http\Exceptions\NotFoundHttpException;
use Jeht\Http\Exceptions\MethodNotAllowedHttpException;
use Laravel\SerializableClosure\SerializableClosure;


/**
 * Adapted from Laravel's Illuminate\Routing\RouteCollection
 * with methods from Laravel's Illuminate\Routing\AbstractRouteCollection
 *
 */
class CompiledRouteCollection extends AbstractRouteCollection implements Countable, Serializable, IteratorAggregate, RouteCollectionInterface
{
	/**
	 * The compield routes
	 *
	 * @var array
	 */
	protected $compiled;

	/**
	 * The compield route attributes
	 *
	 * @var array
	 */
	protected $attributes;

	/**
	 * An array of the routes keyed by method.
	 *
	 * @var \Jeht\Routing\RouteCollection
	 */
	protected $routes;

	/**
	 * A look-up table of routes by their names.
	 *
	 * @var \Jeht\Routing\Router
	 */
	protected $router;

	/**
	 * A look-up table of routes by controller action.
	 *
	 * @var \Jeht\Container\Container
	 */
	protected $container;

	/**
	 * Create a new CompiledRouteCollection instance
	 *
	 * @param array $compiled
	 * @param array $attributes
	 * @return void
	 */
	public function __construct(array $compiled, array $attributes)
	{
		$this->compiled = $compiled;
		$this->attributes = $attributes;
		$this->routes = new RouteCollection;
	}

	/**
	 * Set the router.
	 *
	 * @param \Jeht\Routing\Route $router
	 * @return $this
	 */
	public function setRouter(Router $router)
	{
		$this->router = $router;
		//
		return $this;
	}

	/**
	 * Set the container.
	 *
	 * @param \Jeht\Container\Container $container
	 * @return $this
	 */
	public function setContainer(Container $container)
	{
		$this->container = $container;
		//
		return $this;
	}

	/**
	 * Add a Route instance to the collection.
	 *
	 * @param  \Jeht\Interfaces\Routing\RouteInterface  $route
	 * @return \Jeht\Interfaces\Routing\RouteInterface
	 */
	public function add(RouteInterface $route)
	{
		return $this->routes->add($route);
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
		return $this->routes->refreshNameLookups();
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
		return $this->routes->refreshActionLookups();
	}

	/**
	 * Find the first route matching a given request.
	 *
	 * @param  \Jeht\Http\Request  $request
	 * @return \Jeht\Interfaces\Routing\RouteInterface
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
	 * @param  \Jeht\Interfaces\Routing\RouteInterface|null  $route
	 * @return \Jeht\Interfaces\Routing\RouteInterface
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
	 * @param  \Jeht\Interfaces\Routing\RouteInterface[]  $routes
	 * @param  \Jeht\Http\Request  $request
	 * @param  bool  $includingMethod
	 * @return \Jeht\Interfaces\Routing\RouteInterface|null
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
	 * @return \Jeht\Interfaces\Routing\RouteInterface
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
	 * @return \Jeht\Interfaces\Routing\RouteInterface[]
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
	 * @return \Jeht\Interfaces\Routing\RouteInterface|null
	 */
	public function getByName($name)
	{
		return $this->nameList[$name] ?? null;
	}

	/**
	 * Get a route instance by its controller action.
	 *
	 * @param  string  $action
	 * @return \Jeht\Interfaces\Routing\RouteInterface|null
	 */
	public function getByAction($action)
	{
		return $this->actionList[$action] ?? null;
	}

	/**
	 * Get all of the routes in the collection.
	 *
	 * @return \Jeht\Interfaces\Routing\RouteInterface[]
	 */
	public function getRoutes()
	{
		return $this->compiled;
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
	 * @return \Jeht\Interfaces\Routing\RouteInterface[]
	 */
	public function getRoutesByName()
	{
		return $this->nameList;
	}

	/**
	 * Prepares for the serialization process.
	 *
	 * @return void
	 */
	public function __sleep()
	{
		$this->router = null;
		$this->container = null;
		//
		return ['compiled','attributes'];
	}

	/**
	 * Restores the object state from a cache or a slumber file.
	 *
	 * @return static
	 */
	public static function __set_state(array $data): object
	{
		return new static(
			$data['compiled'], $data['attributes']
		);
	}

	/**
	 * Returns an array of properties to be serialized
	 *
	 * @return array
	 */
	public function __serialize()
	{
		$compiled = serialize(array_map(function($item) {
			return serialize($item);
		}, $this->compiled));
		//
		$attributes = serialize(array_map(function($item) {
			if (isset($item['action']['uses']) && $item['action']['uses'] instanceof Closure) {
				$item['action']['uses'] = serialize(
					new SerializableClosure($item['action']['uses'])
				);
			}
			//
			return serialize($item);
		}, $this->attributes));
		//
		return compact('compiled','attributes');
	}

	/**
	 * Compiles into a PHP serialized format
	 *
	 * @return string
	 */
	public function serialize()
	{
		return serialize($this->__serialize());
	}

	/**
	 * Restores data from a PHP serialized format.
	 *
	 * @param string $data
	 * @return void
	 */
	public function __unserialize(array $data)
	{
		$this->compiled = array_map(function($item) {
			return unserialize($item);
		}, unserialize($data['compiled']));
		//
		$this->attributes = array_map(function($item) {
			return unserialize($item);
		}, unserialize($data['attributes']));
		//
		$this->routes = new RouteCollection;
	}

	/**
	 * Restores data from a PHP serialized format.
	 *
	 * @param string $data
	 * @return void
	 */
	public function unserialize(string $data)
	{
		$restored = unserialize($data);
		//
		$this->__unserialize($restored);
	}

	/**
	 * Returns a RouteCollection instance with same data.
	 *
	 * @return \Jeht\Routing\RouteCollection
	 */
	public function toRouteCollection()
	{
		$collection = new RouteCollection;
		//
		foreach ($this as $route) {
			$collection->add(Route::fromCompiledRoute($route));
		}
		//
		return $collection;
	}

	/**
	 * Restores the object state from a cache or a slumber file.
	 *
	 * @return static
	 */
	public static function createFromSlumber(array $data)
	{
		$static = new static([], []);
		//
		$static->__unserialize($data);
		//
		return $static;
	}

}

<?php
namespace Jeht\Routing;

use Countable;
use IteratorAggregate;
use ArrayIterator;
use Jeht\Interfaces\Routing\RouteCollectionInterface;
use Jeht\Interfaces\Routing\RouteInterface;
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
abstract class AbstractRouteCollection implements Countable, IteratorAggregate, RouteCollectionInterface
{
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

		return $routes->merge($fallbacks)->first(function (RouteInterface $route) use ($request, $includingMethod) {
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

}

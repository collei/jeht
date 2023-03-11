<?php
namespace Jeht\Routing\Interfaces;

use Jeht\Routing\Router;
use Jeht\Container\Container;
use Jeht\Http\Interfaces\Request;

/**
 * Represents a compiled version of the Route in the system.
 *
 */
interface RouteInterface
{
	/**
	 * Run the route action and return the response.
	 *
	 * @return mixed
	 */
	public function run();

	/**
	 * Get the domain defined for the route.
	 *
	 * @return string|null
	 */
	public function getDomain();

	/**
	 * Get the action array or one of its properties for the route.
	 *
	 * @param  string|null  $key
	 * @return mixed
	 */
	public function getAction($key = null);

	/**
	 * Get the dispatcher for the route's controller.
	 *
	 * @return \Jeht\Routing\ControllerDispatcherInterface\Interfaces
	 */
	public function controllerDispatcher();

	/**
	 * Get the controller instance for the route.
	 *
	 * @return mixed
	 */
	public function getController();

	/**
	 * Get the controller method used for the route.
	 *
	 * @return string
	 */
	public function getControllerClass();

	/**
	 * Get the compiled regex expression for the uri.
	 *
	 * @return string
	 */
	public function regex();

	/**
	 * Get the uri.
	 *
	 * @return string
	 */
	public function uri();

	/**
	 * Checks if the given $requestUri matches the route.
	 *
	 * @param Jeht\Http\Interfaces\Request $request
	 * @param bool $includingMethod
	 * @return bool
	 */
	public function matches(Request $request, bool $includingMethod = true);

	/**
	 * Bind the route to a given $request for execution.
	 *
	 * @param \Jeht\Http\Request $request
	 * @return $this
	 */
	public function bind(Request $request);

	/**
	 * Returns the route name
	 *
	 * @return string
	 */
	public function getName();

	/**
	 * Returns the route uri
	 *
	 * @return string
	 */
	public function getUri();

	/**
	 * Checks if the route has parameters.
	 *
	 * @return bool
	 */
	public function hasParameters();

	/**
	 * Checks if the route parameter $name exists.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function hasParameter(string $name);

	/**
	 * Returns all route parameters.
	 *
	 * @return $array
	 * @throws \LogicException
	 */
	public function parameters();

	/**
	 * Get a given parameter from the route.
	 *
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public function parameter(string $name, $default = null);

	/**
	 * Set a parameter to the given route.
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	public function setParameter(string $name, $value);

	/**
	 * Unset a parameter on the route.
	 *
	 * @param string $name
	 * @return void
	 */
	public function forgetParameter(string $name);

	/**
	 * Get a key/value list of parameters without null values.
	 *
	 * @return array
	 */
	public function parametersWithoutNulls();

	/**
	 * Mark this route as a fallback route.
	 *
	 * @return $this
	 */
	public function fallback();

	/**
	 * Set the fallback value.
	 *
	 * @param bool $isFallback
	 * @return $this
	 */
	public function setFallback(bool $isFallback);

	/**
	 * Returns whether the route is a fallback.
	 *
	 * @return bool
	 */
	public function isFallback();

	/**
	 * Set the router.
	 *
	 * @param \Jeht\Routing\Router $router
	 * @return $this
	 */
	public function setRouter(Router $router);

	/**
	 * Set the container.
	 *
	 * @param \Jeht\Container\Container $container
	 * @return $this
	 */
	public function setContainer(Container $container);

	/**
	 * Get the HTTP verbs the route responds to.
	 *
	 * @return array
	 */
	public function methods();

	/**
	 * Get all middleware, including the ones from the controller.
	 *
	 * @return array
	 */
	public function gatherMiddleware();

	/**
	 * Get or set the middlewares attached to the route.
	 *
	 * @param  array|string|null  $middleware
	 * @return $this|array
	 */
	public function middleware($middleware = null);

	/**
	 * Specify that the "Authorize" / "can" middleware should be applied
	 * to the route with the given options.
	 *
	 * @param  string  $ability
	 * @param  array|string  $models
	 * @return $this
	 */
	public function can($ability, $models = []);

	/**
	 * Get the middleware for the route's controller.
	 *
	 * @return array
	 */
	public function controllerMiddleware();

	/**
	 * Specify middleware that should be removed from the given route.
	 *
	 * @param  array|string  $middleware
	 * @return $this
	 */
	public function withoutMiddleware($middleware);

	/**
	 * Get the middleware should be removed from the route.
	 *
	 * @return array
	 */
	public function excludedMiddleware();
}


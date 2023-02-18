<?php
namespace Jeht\Interfaces\Routing;

use Jeht\Routing\Route;

/**
 * From Laravel's Illuminate\Routing\Contracts\ControllerDispatcher
 *
 */
interface ControllerDispatcherInterface
{
	/**
	 * Dispatch a request to a given controller and method.
	 *
	 * @param  \Jeht\Routing\Route  $route
	 * @param  mixed  $controller
	 * @param  string  $method
	 * @return mixed
	 */
	public function dispatch(Route $route, $controller, $method);

	/**
	 * Get the middleware for the controller instance.
	 *
	 * @param  \Illuminate\Routing\Controller  $controller
	 * @param  string  $method
	 * @return array
	 */
	public function getMiddleware($controller, $method);
}



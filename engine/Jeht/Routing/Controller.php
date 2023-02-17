<?php
namespace Jeht\Routing;

use BadMethodCallException;

/**
 * Adapetd from Laravel's Illuminate\Routing\Controller
 *
 */
abstract class Controller
{
	/**
	 * The middleware registered on the controller.
	 *
	 * @var array
	 */
	protected $middleware = [];

	/**
	 * Register middleware on the controller.
	 *
	 * @param  \Closure|array|string  $middleware
	 * @param  array  $options
	 * @return \Jeht\Routing\ControllerMiddlewareOptions
	 */
	public function middleware($middleware, array $options = [])
	{
		foreach ((array) $middleware as $m) {
			$this->middleware[] = [
				'middleware' => $m,
				'options' => &$options,
			];
		}
		//
		return new ControllerMiddlewareOptions($options);
	}

	/**
	 * Get the middleware assigned to the controller.
	 *
	 * @return array
	 */
	public function getMiddleware()
	{
		return $this->middleware;
	}

	/**
	 * Execute an action on the controller.
	 *
	 * @param  string  $method
	 * @param  array  $parameters
	 * @return \Jeht\Http\Response
	 */
	public function callAction($method, $parameters)
	{
		return call_user_func_array([$this, $method], $parameters);
	}

	/**
	 * Handle calls to missing methods on the controller.
	 *
	 * @param  string  $method
	 * @param  array  $parameters
	 * @return mixed
	 *
	 * @throws \BadMethodCallException
	 */
	public function __call($method, $parameters)
	{
		throw new BadMethodCallException(
			'Method '.static::class.'::'.$method.' does not exist.'
		);
	}
}



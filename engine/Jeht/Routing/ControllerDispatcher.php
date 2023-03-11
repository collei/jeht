<?php
namespace Jeht\Routing;

use Jeht\Container\Container;
use Jeht\Routing\Interfaces\RouteInterface;
use Jeht\Routing\Interfaces\ControllerDispatcherInterface;
use Jeht\Routing\Traits\RouteDependencyResolverTrait;
use Jeht\Collections\Collection;

/**
 * Adapted from Laravel's \Illuminate\Routing\ControllerDispatcher
 *
 */
class ControllerDispatcher implements ControllerDispatcherInterface
{
	use RouteDependencyResolverTrait;

	/**
	 * The container instance.
	 *
	 * @var \Jeht\Container\Container
	 */
	protected $container;

	/**
	 * Create a new controller dispatcher instance.
	 *
	 * @param  \Jeht\Container\Container  $container
	 * @return void
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * Dispatch a request to a given controller and method.
	 *
	 * @param  \Jeht\Routing\Interfaces\RouteInterface  $route
	 * @param  mixed  $controller
	 * @param  string  $method
	 * @return mixed
	 */
	public function dispatch(RouteInterface $route, $controller, $method)
	{
		$parameters = $this->resolveClassMethodDependencies(
			$route->parametersWithoutNulls(), $controller, $method
		);

		if (method_exists($controller, 'callAction')) {
			return $controller->callAction($method, $parameters);
		}

		return $controller->{$method}(...array_values($parameters));
	}

	/**
	 * Get the middleware for the controller instance.
	 *
	 * @param  \Jeht\Routing\Controller  $controller
	 * @param  string  $method
	 * @return array
	 */
	public function getMiddleware($controller, $method)
	{
		if (! method_exists($controller, 'getMiddleware')) {
			return [];
		}

		return Collection::for($controller->getMiddleware())->reject(function ($data) use ($method) {
			return static::methodExcludedByOptions($method, $data['options']);
		})->pluck('middleware')->all();
	}

	/**
	 * Determine if the given options exclude a particular method.
	 *
	 * @param  string  $method
	 * @param  array  $options
	 * @return bool
	 */
	protected static function methodExcludedByOptions($method, array $options)
	{
		return (isset($options['only']) && ! in_array($method, (array) $options['only'])) ||
			(! empty($options['except']) && in_array($method, (array) $options['except']));
	}
}


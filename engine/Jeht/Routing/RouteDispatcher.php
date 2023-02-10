<?php
namespace Jeht\Routing;

use Jeht\Interfaces\Http\Request;
use Jeht\Container\Container;

class RouteDispatcher
{
	private $app;

	public function __construct(Container $app = null)
	{
		if (is_null($app)) {
			$app = Container::getInstance();
		}
		//
		$this->app = $app;
	}

	public function dispatch(Request $request, $handler)
	{

		if ($handler instanceof Closure) {
			return $handler($request);
		}
		//
		if (is_array($handler)) {
			[$class, $method] = $handler;
		} elseif (false !== strpos($handler, '@')) {
			[$class, $method] = explode('@', $handler, 2);
		} elseif (false !== strpos($handler, '@')) {
			[$class, $method] = explode('@', $handler, 2);
		} else {
			$class = $handler;
			$method = 'nonononono';						
		}
		//
		$instance = $this->app->make($class);
		//
		if (method_exists($instance, $method)) {
			return $instance->{$method}($request);
		}
		//
		throw new \RuntimeException("Método [$method] não encontrado na classe [$class] !");

	}
}


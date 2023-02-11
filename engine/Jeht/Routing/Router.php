<?php
namespace Jeht\Routing;

use Jeht\Ground\Application;

class Router
{
	/**
	 * @var Jeht\Ground\Application
	 */
	private $app;

	/**
	 * @var array
	 */
	private $routes = [];

	public function __construct(Application $app)
	{
		$this->app = $app;
	}


	/**
	 * Tests the given $requestUri against $regex.
	 * Returns an array with two elements: the boolean result of the match
	 * and an associative array of parameters which may be empty.
	 *
	 * @param string $requestUri
	 * @param string $regex
	 * @return [bool, array]
	 */
	public static function requestMatchesRegex(
		string $requestUri, string $regex
	) {
		$bool = (1 === preg_match($regex, $requestUri, $matches));
		$parameters = [];
		//
		if ($bool && !empty($matches)) {
			foreach ($matches as $key => $value) {
				if (is_string($key)) {
					$parameters[$key] = $value;
				}
			}
		}
		//
		return [$bool, $parameters];
	}

	public function registerRoute(Route $route)
	{
		$this->routes[] = $route;
	}

	public function dispatch($request)
	{
		foreach ($this->routes as $route) {
			if ($route->matches($request)) {
				return $route->runRoute($request);
			}
		}
		//
		throw new \Exception('Route not found for request URI: ' . $request->getUri());
	}

}


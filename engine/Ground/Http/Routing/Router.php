<?php
namespace Ground\Http\Routing;

use Ground\Http\Routing\RouteGroup;

class Router
{
	/**
	 * @var array[][RouteInterface, HttpServlet]
	 */
	private $routes = [];


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

		echo '<fieldset><legend>' . $route->getPath() . '</legend><pre>' . print_r($route,true) . '</pre></fieldset>';
	}


}


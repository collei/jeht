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
		echo '<div>'.__METHOD__.'('.__LINE__.'):<br> instanceof '.__CLASS__.' has memorized '.count($this->routes).' routes<br>trying: '.$request->getUri()->getPath().'</div>';

		foreach ($this->routes as $route) {

			echo '<fieldset><textarea style="width:100%;" rows="10">' . print_r($route,true) . '</textarea></fieldset>';

			if ($route->matches($request)) {
				return $route->runRoute($request);
			}
		}
		//
		return 'PIMBA'; //null;
	}


}


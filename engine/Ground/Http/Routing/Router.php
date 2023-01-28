<?php
namespace Ground\Http\Routing;

use Ground\Http\Contracts\RouteInterface;
use Ground\Http\Servlets\HttpServlet;

class Router
{
	/**
	 * @var string
	 */
	protected const REGEX_IU_PARAM = '/\\{(\\w+)(\\?)?\\}/';

	/**
	 * @var string
	 */
	protected const REGEX_ANY = '[^\\/]+';

	/**
	 * @var array[][RouteInterface, HttpServlet]
	 */
	private static $routes = [];

	/**
	 * Translates a route path into a regex that may be used to collect
	 * named parameters easily.
	 *
	 * Use the second parameter to override the default regex piece for
	 * one or more parameters, so you can add custom constraints for, e.g.,
	 * alphanumeric. The default piece matches anything but forward slashes.
	 *
	 * @param string $path
	 * @param array $paramRegex
	 * @return string|false
	 */
	public static function pathToRegex(string $path, array $paramRegex = [])
	{
		if (preg_match_all(self::REGEX_IU_PARAM, $path, $matches, PREG_SET_ORDER)) {
			$regex = $path;
			//
			foreach ($matches as $match) {
				$regexp = self::REGEX_ANY;
				//
				// override a parameter regex piece if set
				if (array_key_exists($match[1], $paramRegex)) {
					$regexp = $paramRegex[$match[1]];
				}
				//
				$piece = '(?P<' . $match[1] . '>' . $regexp . ')';
				//
				// if parameter is defined as optional...
				if (isset($match[2])) {
					$piece .= '?';
				}
				//
				$regex = str_replace($match[0], $piece, $regex);
			}
			//
			return $regex;
		}
		//
		return false;
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
		$bool = (1 == preg_match($regex, $requestUri, $matches));
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

}


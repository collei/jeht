<?php
namespace Ground\Http\Routing;

use Ground\Http\Routing\Router;
use Ground\Http\Routing\RouteFactory;
use Ground\Http\Routing\RouteGroup;
use Ground\Http\Servlets\HttpServlet;

class RouteRegistrar
{
	/**
	 * @var string[]
	 */
	protected const HTTP_METHODS = [
		'GET','POST','PUT','PATCH','OPTIONS','HEAD','DELETE'
	];

	/**
	 * @var @static \Ground\Http\Routing\RouteGroup
	 */
	protected static $routeGroup = null;

	protected static function getRouteGroup()
	{
		if (! is_null(self::$routeGroup)) {
			return self::$routeGroup;
		}
		//
		return self::$routeGroup = new RouteGroup;
	}

	protected static function registerRoute(array $methods, string $uri, $handler)
	{
		$this->routes[] = $factory = new RouteFactory(
			$uri, $handler, $methods, self::getRouteGroup()
		);
		//
		return $factory;
	}

	public static function get(string $uri, $handler)
	{
		return self::registerRoute(['GET'], $uri, $handler);
	}

	public static function head(string $uri, $handler)
	{
		return self::registerRoute(['HEAD'], $uri, $handler);
	}

	public static function post(string $uri, $handler)
	{
		return self::registerRoute(['POST'], $uri, $handler);
	}

	public static function patch(string $uri, $handler)
	{
		return self::registerRoute(['PATCH'], $uri, $handler);
	}

	public static function put(string $uri, $handler)
	{
		return self::registerRoute(['PUT'], $uri, $handler);
	}

	public static function options(string $uri, $handler)
	{
		return self::registerRoute(['OPTIONS'], $uri, $handler);
	}

	public static function delete(string $uri, $handler)
	{
		return self::registerRoute(['DELETE'], $uri, $handler);
	}

	public static function any(string $uri, $handler)
	{
		return self::registerRoute(self::HTTP_METHODS, $uri, $handler);
	}

	public static function request(array $methods, string $uri, $handler)
	{
		$upperMethods = array_map(function($item) {
			return strtoupper($item);
		}, $methods);

		$valid = array_intersect($upperMethods, self::HTTP_METHODS);

		if (count($valid) !== count($methods)) {
			throw new InvalidArgumentException(
				'One of the given HTTP methods is invalid: ' . implode(', ', $methods)
			);
		}

		return self::registerRoute($upperMethods, $uri, $handler);
	}

	public static function name(string $name)
	{
		return self::getRouteGroup()->name($name);
	}

	public static function prefix(string $prefix)
	{
		return self::getRouteGroup()->prefix($prefix);
	}

	public static function controller(string $controller)
	{
		return self::getRouteGroup()->controller($controller);
	}

	public static function namespace(string $namespace)
	{
		return self::getRouteGroup()->namespace($namespace);
	}

/**
 * @todo define route generator
 */
/*
	public static function blergh($path, $controller = null)
	{
		$currents = self::getRouteGroup()->getCurrent();
		//
		echo '<fieldset><legend>' . $path . '</legend><pre>' . print_r($currents, true) . '</pre></fieldset>';
	}
 */
	
}





<?php
namespace Ground\Http\Routing;

use Ground\Support\Facades\Facade;
use Ground\Http\Routing\RouteRegistrar;
use Ground\Http\Routing\Router;

class RouteFacade extends Facade
{
	protected static $router;

	protected static function resolveInstance()
	{
		return new RouteRegistrar(
			self::$router = new Router()
		);
	}
}


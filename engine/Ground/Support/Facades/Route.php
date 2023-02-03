<?php
namespace Ground\Support\Facades;

use Ground\Http\Routing\RouteRegistrar;
use Ground\Http\Routing\Router;

class Route extends Facade
{
	protected static function getFacadeAccessor()
	{
		return 'route.registrar';
	}
}


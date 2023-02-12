<?php
namespace Jeht\Support\Facades;

use Jeht\Routing\RouteRegistrar;
use Jeht\Routing\Router;

class Route extends Facade
{
	protected static function getFacadeAccessor()
	{
		return 'router';
	}
}


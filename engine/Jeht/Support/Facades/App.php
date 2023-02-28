<?php
namespace Jeht\Support\Facades;

use Jeht\Ground\Application;

class App extends Facade
{
	protected static function getFacadeAccessor()
	{
		return 'app';
	}
}


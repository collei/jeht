<?php
namespace Jeht\Support\Facades;

use Jeht\Events\Dispatcher;

class Event extends Facade
{
	protected static function getFacadeAccessor()
	{
		return 'events';
	}
}


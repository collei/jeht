<?php
namespace Jeht\Support\Facades;

use Jeht\Log\Logger;

class Log extends Facade
{
	protected static function getFacadeAccessor()
	{
		return 'log';
	}
}


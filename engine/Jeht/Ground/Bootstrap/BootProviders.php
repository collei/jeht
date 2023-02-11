<?php
namespace Jeht\Ground\Bootstrap;

use Jeht\Interfaces\Ground\Application;

/**
 * Adapted from \Illuminate\Foundation\Bootstrap\BootProviders
 * @link https://laravel.com/api/8.x/Illuminate/Foundation/Bootstrap/BootProviders.html
 * @link https://github.com/laravel/framework/blob/8.x/src/Illuminate/Foundation/Bootstrap/BootProviders.php
 *
 */
class BootProviders
{
	/**
	 * Bootstrap the given application.
	 *
	 * @param \Jeht\Interfaces\Ground\Application $app
	 * @return void
	 */
	public function bootstrap(Application $app)
	{
		$app->boot();
	}
}


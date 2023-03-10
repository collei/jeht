<?php
namespace Jeht\Ground\Bootstrap;

use Jeht\Ground\Interfaces\Application;

/**
 * Adapted from Laravel's Illuminate\Foundation\Bootstrap\RegisterProviders
 * @link https://laravel.com/api/8.x/Illuminate/Foundation/Bootstrap/RegisterProviders.html
 * @link https://github.com/laravel/framework/blob/8.x/src/Illuminate/Foundation/Bootstrap/RegisterProviders.php
 *
 */
class RegisterProviders
{
	/**
	 * Bootstrap the given application.
	 *
	 * @param  \Jeht\Ground\Interfaces\Application  $app
	 * @return void
	 */
	public function bootstrap(Application $app)
	{
		$app->registerConfiguredProviders();
	}
}


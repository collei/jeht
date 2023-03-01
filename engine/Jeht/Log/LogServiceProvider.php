<?php
namespace Jeht\Log;

use Jeht\Support\ServiceProvider;

/**
 * Adapted from Laravel's Illuminate\Log\LogServiceProvider
 * @link https://laravel.com/api/8.x/Illuminate/Log/LogServiceProvider.html
 * @link https://github.com/laravel/framework/blob/8.x/src/Illuminate/Log/LogServiceProvider.php
 *
 */
class LogServiceProvider extends ServiceProvider
{
	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->singleton('log', function ($app) {
			return new LogManager($app);
		});
	}
}


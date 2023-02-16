<?php
namespace Jeht\Routing;

use Jeht\Support\ServiceProvider;

class RoutingServiceProvider extends ServiceProvider
{
	/**
	 * Register the service provider
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerRouter();
	}

	/**
	 * Register the Router instance
	 *
	 * @return void
	 */
	protected function registerRouter()
	{
		$this->app->singleton('router', function($app) {
			return new Router($app);
		});
	}

}

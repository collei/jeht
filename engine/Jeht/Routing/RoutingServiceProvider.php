<?php
namespace Jeht\Routing;

use Jeht\Support\ServiceProvider;

class RoutingServiceProvider extends ServiceProvider
{
	public function register()
	{
		$this->registerRouter();
	}

	protected function registerRouter()
	{
		$this->app->singleton('router', function($app) {
			return new Router($app);
		});
	}
}

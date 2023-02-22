<?php
namespace Jeht\Routing;

use Jeht\Support\ServiceProvider;
use Jeht\Interfaces\Routing\ControllerDispatcherInterface;

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
		$this->registerControllerDispatcher();
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

	/**
	 * Register the Router instance
	 *
	 * @return void
	 */
	protected function registerControllerDispatcher()
	{
		$this->app->singleton(ControllerDispatcherInterface::class, function($app) {
			return new ControllerDispatcher($app);
		});
	}

}

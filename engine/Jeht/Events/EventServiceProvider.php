<?php
namespace Jeht\Events;

use Jeht\Support\ServiceProvider;
use Jeht\Ground\Application;

class EventServiceProvider extends ServiceProvider
{
	/**
	 * Register the service provider
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->singleton('events', function($app) {
			return (new Dispatcher($app))->setListenerProvider(new ListenerProvider);
		});
	}

}

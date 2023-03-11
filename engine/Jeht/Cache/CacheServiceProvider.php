<?php
namespace Jeht\Cache;

use Jeht\Support\ServiceProvider;
use Jeht\Support\Interfaces\DeferrableProvider;

class CacheServiceProvider extends ServiceProvider implements DeferrableProvider
{
	/**
	 * Register the service provider
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->singleton('cache', function($app) {
			return new CacheManager($app);
		});

		$this->app->singleton('cache.store', function($app) {
			return $app['cache']->driver();
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return ['cache', 'cache.store'];
	}

}

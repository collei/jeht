<?php
namespace Jeht\Ground\Support\Providers;

use Jeht\Ground\Events\DiscoverEvents;
use Jeht\Support\Facades\Event;
use Jeht\Support\ServiceProvider;
use Jeht\Collections\Collection;

/**
 * Adapted from Laravel's Illuminate\Foundation\Support\Providers\EventServiceProvider
 * @link https://laravel.com/api/8.x/Illuminate/Foundation/Support/Providers/EventServiceProvider.html
 * @link https://github.com/laravel/framework/blob/8.x/src/Illuminate/Foundation/Support/Providers/EventServiceProvider.php
 *
 */
class EventServiceProvider extends ServiceProvider
{
	/**
	 * The event handler mappings for the application.
	 *
	 * @var array
	 */
	protected $listen = [];

	/**
	 * The subscriber classes to register.
	 *
	 * @var array
	 */
	protected $subscribe = [];

	/**
	 * Register the application's event listeners.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->booting(function () {
			$events = $this->getEvents();

			foreach ($events as $event => $listeners) {
				foreach (array_unique($listeners) as $listener) {
					Event::listen($event, $listener);
				}
			}

			foreach ($this->subscribe as $subscriber) {
				Event::subscribe($subscriber);
			}
		});
	}

	/**
	 * Boot any application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		//
	}

	/**
	 * Get the events and handlers.
	 *
	 * @return array
	 */
	public function listens()
	{
		return $this->listen;
	}

	/**
	 * Get the discovered events and listeners for the application.
	 *
	 * @return array
	 */
	public function getEvents()
	{
		if ($this->app->eventsAreCached()) {
			$cache = require $this->app->getCachedEventsPath();

			return $cache[get_class($this)] ?? [];
		} else {
			return array_merge_recursive(
				$this->discoveredEvents(),
				$this->listens()
			);
		}
	}

	/**
	 * Get the discovered events for the application.
	 *
	 * @return array
	 */
	protected function discoveredEvents()
	{
		return $this->shouldDiscoverEvents()
					? $this->discoverEvents()
					: [];
	}

	/**
	 * Determine if events and listeners should be automatically discovered.
	 *
	 * @return bool
	 */
	public function shouldDiscoverEvents()
	{
		return false;
	}

	/**
	 * Discover the events and listeners for the application.
	 *
	 * @return array
	 */
	public function discoverEvents()
	{
		return Collection::for($this->discoverEventsWithin())
					->reject(function ($directory) {
						return ! is_dir($directory);
					})
					->reduce(function ($discovered, $directory) {
						return array_merge_recursive(
							$discovered,
							DiscoverEvents::within($directory, $this->eventDiscoveryBasePath())
						);
					}, []);
	}

	/**
	 * Get the listener directories that should be used to discover events.
	 *
	 * @return array
	 */
	protected function discoverEventsWithin()
	{
		return [
			$this->app->path('Listeners'),
		];
	}

	/**
	 * Get the base path to be used during event discovery.
	 *
	 * @return string
	 */
	protected function eventDiscoveryBasePath()
	{
		return base_path();
	}
}


<?php
namespace Jeht\Ground\Events;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use SplFileInfo;
use Jeht\Support\Str;
use Jeht\Support\Reflector;
use Jeht\Support\Facades\App;
use Jeht\Collections\Collection;
use Jeht\Filesystem\Folder;

/**
 * Adapted from Laravel's Illuminate\Foundation\Events\DiscoverEvents
 * @link https://laravel.com/api/8.x/Illuminate/Foundation/Events/DiscoverEvents.html
 * @link https://github.com/laravel/framework/blob/8.x/src/Illuminate/Foundation/Events/DiscoverEvents.php
 *
 */
class DiscoverEvents
{
	/**
	 * Get all of the events and listeners by searching the given listener directory.
	 *
	 * @param  string  $listenerPath
	 * @param  string  $basePath
	 * @return array
	 */
	public static function within($listenerPath, $basePath)
	{
		$listeners = Collection::for(static::getListenerEvents(
			Folder::for($listenerPath)->files()->asNative()->get(), $basePath
		));

		$discoveredEvents = [];

		foreach ($listeners as $listener => $events) {
			foreach ($events as $event) {
				if (! isset($discoveredEvents[$event])) {
					$discoveredEvents[$event] = [];
				}

				$discoveredEvents[$event][] = $listener;
			}
		}

		return $discoveredEvents;
	}

	/**
	 * Get all of the listeners and their corresponding events.
	 *
	 * @param  iterable  $listeners
	 * @param  string  $basePath
	 * @return array
	 */
	protected static function getListenerEvents($listeners, $basePath)
	{
		$listenerEvents = [];

		foreach ($listeners as $listener) {
			try {
				$listener = new ReflectionClass(
					static::classFromFile($listener, $basePath)
				);
			} catch (ReflectionException $e) {
				continue;
			}

			if (! $listener->isInstantiable()) {
				continue;
			}

			foreach ($listener->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
				if (
					! Str::is('handle*', $method->name) ||
					! isset($method->getParameters()[0])
				) {
					continue;
				}

				$listenerEvents[$listener->name.'@'.$method->name] =
								Reflector::getParameterClassNames($method->getParameters()[0]);
			}
		}

		return array_filter($listenerEvents);
	}

	/**
	 * Extract the class name from the given file path.
	 *
	 * @param  \SplFileInfo  $file
	 * @param  string  $basePath
	 * @return string
	 */
	protected static function classFromFile(SplFileInfo $file, $basePath)
	{
		$class = trim(Str::replaceFirst($basePath, '', $file->getRealPath()), DIRECTORY_SEPARATOR);

		return str_replace(
			[DIRECTORY_SEPARATOR, ucfirst(basename(app()->path())).'\\'],
			['\\', app()->getNamespace()],
			ucfirst(Str::replaceLast('.php', '', $class))
		);
	}
}


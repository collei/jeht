<?php
namespace Jeht\Events;

use Jeht\Events\Interfaces\ListenerProviderInterface;
use Jeht\Events\Interfaces\EventInterface;
use Jeht\Support\Traits\InheritanceAware;

/**
 * Mapper from an event to the listeners that are applicable to that event.
 */
class ListenerProvider implements ListenerProviderInterface
{
	use InheritanceAware;

	/**
	 * @var object[][]
	 */
	protected $listeners;

	/**
	 * Create a new instance of hte provider.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->listeners = [];
	}

	/**
	 * Register an event listener for the given event instance.
	 *
	 * @param string|object $listener
	 * @param string|object $event
	 * @return void
	 */
	public function addListener($listener, $event)
	{
		$class = is_object($event) ? get_class($event) : $event;
		//
		$this->insertListener($listener, $class);
		//
		if ($parents = $this->filteredParents($event, AbstractEvent::class)) {
			foreach ($parents as $parent) {
				$this->insertListener($listener, $parent);
			}
		}
		//
		if ($interfaces = $this->filteredInterfaces($event, EventInterface::class)) {
			foreach ($interfaces as $interface) {
				$this->insertListener($listener, $interface);
			}
		}
	}

	/**
	 * Register an event listener for the given event (a class or interface name).
	 *
	 * @param string|object|callable $listener
	 * @param string $eventClassName
	 * @return void
	 */
	protected function insertListener($listener, string $eventClassName)
	{
		if (! isset($this->listeners[$eventClassName])) {
			$this->listeners[$eventClassName] = []; 
		}
		//
		$this->listeners[$eventClassName][] = is_object($listener)
			? get_class($listener)
			: $listener;
	}

	/**
	 * @param object $event
	 *   An event for which to return the relevant listeners.
	 * @return iterable<callable>
	 *   An iterable (array, iterator, or generator) of callables.  Each
	 *   callable MUST be type-compatible with $event.
	 */
	public function getListenersForEvent(object $event) : iterable
	{
		$classes = [];
		//
		if ($parents = $this->filteredParents($event, AbstractEvent::class)) {
			$classes = $classes + $parents;
		}
		//
		if ($interfaces = $this->filteredInterfaces($event, EventInterface::class)) {
			$classes = $classes + $interfaces;
		}
		//
		$eventListeners = [];
		//
		foreach ($this->listeners as $event => $listeners) {
			if (in_array($event, $classes)) {
				$eventListeners = $eventListeners + $listeners; 
			}
		}
		//
		return $eventListeners;
	}
}

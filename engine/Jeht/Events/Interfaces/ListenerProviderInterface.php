<?php
namespace Jeht\Events\Interfaces;

use Psr\EventDispatcher\ListenerProviderInterface as PsrListenerProviderInterface;

/**
 * Mapper from an event to the listeners that are applicable to that event.
 */
interface ListenerProviderInterface extends PsrListenerProviderInterface
{
	/**
	 * Register an event listener for the given event instance.
	 *
	 * @param object $listener
	 * @param object $event
	 * @return $this
	 */
	public function addListener(object $listener, object $event);

	/**
	 * @param object $event
	 *   An event for which to return the relevant listeners.
	 * @return iterable<callable>
	 *   An iterable (array, iterator, or generator) of callables.  Each
	 *   callable MUST be type-compatible with $event.
	 */
	public function getListenersForEvent(object $event) : iterable;
}

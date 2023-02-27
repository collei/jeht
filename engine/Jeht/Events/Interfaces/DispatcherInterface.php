<?php
namespace Jeht\Events\Interfaces;

use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Defines a dispatcher for events.
 */
interface DispatcherInterface extends EventDispatcherInterface
{
	/**
	 * Provide all relevant listeners with an event to process.
	 *
	 * @param object $event The object to process.
	 * @return object The Event that was passed, now modified by listeners.
	 */
	public function dispatch(object $event);
}


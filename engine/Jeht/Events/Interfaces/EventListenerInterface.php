<?php
namespace Jeht\Events\Interfaces;

/**
 * An Event listener.
 *
 */
interface EventListenerInterface
{
	/**
	 * Receives the event for performing some action.
	 *
	 * @param object $event
	 * @return void
	 */
	public function handle(EventInterface $event);
}

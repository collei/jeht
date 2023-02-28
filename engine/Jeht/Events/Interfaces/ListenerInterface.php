<?php
namespace Jeht\Events\Interfaces;

/**
 * An Event listener.
 *
 */
interface ListenerInterface
{
	/**
	 * Receives the event for performing some action.
	 *
	 * @param object $event
	 * @return void
	 */
	public function handle(object $event);
}

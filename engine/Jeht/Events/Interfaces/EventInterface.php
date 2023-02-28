<?php
namespace Jeht\Events\Interfaces;

/**
 * An Event.
 *
 */
interface EventInterface
{
	/**
	 * Retrieves the event name, or the implementing, fully namespaced, class name.
	 *
	 * @return string
	 */
	public function name();

	/**
	 * Returns the event payload, if set.
	 *
	 * @return mixed
	 */
	public function get();

	/**
	 * Returns true if this instance matches another instance.
	 *
	 * @return bool
	 */
	public function matches(object $that);
}

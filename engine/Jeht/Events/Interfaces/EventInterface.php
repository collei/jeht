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
	public function eventName();

	/**
	 * Returns the event sender object.
	 *
	 * @return object
	 */
	public function sender();

	/**
	 * Returns details about the event.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function get(string $key);

	/**
	 * Defines a response item to the emitter.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function respond(string $key, $value);

	/**
	 * Retrieves any response details on the event.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function getResponse(string $key);

	/**
	 * Cancels a response item.
	 *
	 * @param string $key
	 * @return void
	 */
	public function cancelResponse(string $key);

	/**
	 * Returns true if this instance matches another instance.
	 *
	 * @return bool
	 */
	public function matches(object $that);
}

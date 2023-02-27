<?php
namespace Jeht\Events;

use Jeht\Events\Interfaces\EventInterface;
use ReflectionClass;

/**
 * Basic capabilities for the extending Event classes.
 *
 */
abstract class AbstractEvent implements EventInterface
{
	/**
	 * @var object
	 */
	protected $sender;

	/**
	 * @var array
	 */
	protected $payload = [];

	/**
	 * @var array
	 */
	protected $responsePayload = [];

	/**
	 * Creates an instance of descendant classes without calling constructors.
	 *
	 * @return static
	 */
	protected static function birth()
	{
		$refl = new ReflectionClass(static::class);
		//
		return $refl->newInstanceWithoutConstructor();
	}

	/**
	 * Creates an event for the dispatcher. It must have a sender object.
	 * It may have optional payload items.
	 *
	 * @param object $sender
	 * @param array $payload = []
	 * @return static
	 */
	public static function create(object $sender, array $payload = [])
	{
		$event = static::birth();
		$event->sender = $sender;
		//
		foreach ($payload as $key => $value) {
			$event->set($key, $value);
		}
		//
		return $event;
	}

	/**
	 * Creates an event with optional payload item(s).
	 *
	 * @param array $payload = []
	 * @return static
	 */
	public static function with(array $payload = [])
	{
		$event = static::birth();
		//
		foreach ($payload as $key => $value) {
			$event->set($key, $value);
		}
		//
		return $event;
	}

	public function details()
	{
		[$payload, $response] = [$this->payload, $this->responsePayload];

		return json_encode(compact('payload','response'));
	}

	/**
	 * Retrieves the event name, or the implementing, fully namespaced, class name.
	 *
	 * @return string
	 */
	public function eventName()
	{
		return static::class;
	}

	/**
	 * Returns the event sender object.
	 *
	 * @return object
	 */
	public function sender()
	{
		return $this->sender;
	}

	/**
	 * Returns details about the event.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function get(string $key)
	{
		return $this->payload[$key] ?? null;
	}

	/**
	 * Defines details about the event.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	protected function set(string $key, $value)
	{
		$this->payload[$key] = $value;
	}

	/**
	 * Defines a response item to the emitter.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function respond(string $key, $value)
	{
		$this->responsePayload[$key] = $value;
	}

	/**
	 * Retrieves any response details on the event.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function getResponse(string $key)
	{
		return $this->responsePayload[$key] ?? null;
	}

	/**
	 * Cancels a response item.
	 *
	 * @param string $key
	 * @return void
	 */
	public function cancelResponse(string $key)
	{
		unset($this->responsePayload[$key]);
	}

	/**
	 * Returns true if this instance matches another instance, based on
	 * class name and contained data.
	 *
	 * @return bool
	 */
	public function matches(object $that)
	{
		return (get_class($that) == get_class($this))
			&& ($that->payload == $this->payload);
	}
}

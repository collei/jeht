<?php
namespace Jeht\Events;

use Jeht\Events\Interfaces\EventInterface;
use Jeht\Events\Traits\EventsWithResponsePayload;
use Jeht\Interfaces\Support\Jsonable;
use ReflectionClass;
use JsonSerializable;

/**
 * Basic capabilities for the extending Event classes.
 *
 */
abstract class AbstractEvent implements EventInterface, JsonSerializable, Jsonable
{
	/**
	 * @var mixed
	 */
	protected $payload;

	/**
	 * Creates an instance of the event with optional payload.
	 *
	 * @param mixed $payload
	 * @return void
	 */
	public function __construct($payload = null)
	{
		$this->payload = $payload;
	}

	/**
	 * Creates an event for the dispatcher. It may have optional payload.
	 *
	 * @param mixed $payload
	 * @return static
	 */
	public static function create($payload = null)
	{
		return new static($payload);
	}

	/**
	 * Creates an event with optional payload.
	 *
	 * @param mixed $payload
	 * @return static
	 */
	public static function with($payload)
	{
		$event = static::birth();
		//
		$event->set($payload);
		//
		return $event;
	}

	/**
	 * Specify data which should be serialized to JSON.
	 *
	 * @return mixed
	 */
	public function jsonSerialize()
	{
		$event = get_class($this);
		$payload = $this->payload;
		$response = $this->responsePayload ?? null;
		//
		return compact('event','payload','response');
	}

	/**
	 * Convert the object to its JSON representation.
	 *
	 * @param  int  $options
	 * @return string
	 */
	public function toJson($options = 0)
	{
		return json_encode($this, $options);
	}

	/**
	 * Retrieves the event name, or the implementing, fully namespaced, class name.
	 *
	 * @return string
	 */
	public function name()
	{
		return static::class;
	}

	/**
	 * Returns details about the event.
	 *
	 * @return mixed
	 */
	public function get()
	{
		return $this->payload ?? null;
	}

	/**
	 * Defines details about the event.
	 *
	 * @param mixed $payload
	 * @return void
	 */
	protected function set($payload)
	{
		$this->payload = $payload;
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

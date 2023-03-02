<?php
namespace Jeht\Ground\Http\Events;

use Jeht\Events\AbstractEvent;

class RequestHandled extends AbstractEvent
{
	/**
	 * Create a new event instance.
	 *
	 * @param  \Jeht\Http\Request  $request
	 * @param  \Jeht\Http\Response  $response
	 * @return void
	 */
	public function __construct($request, $response)
	{
		parent::__construct(compact('request','response'));
	}

	/**
	 * Exposed readonly properties
	 *
	 * @property \Jeht\Http\Request $request
	 * @property \Jeht\Http\Response $response
	 */
	public function __get(string $name)
	{
		if (! in_array($name, ['request', 'response'], true)) {
			return;
		}
		//
		$properties = $this->payload;
		//
		return $properties[$name] ?? null;
	}

}


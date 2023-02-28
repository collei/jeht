<?php
namespace Jeht\Events\Traits;

/**
 * Trait for implementing events with response payload support 
 */
trait EventsWithResponsePayload
{
	/**
	 * @var mixed
	 */
	protected $responsePayload;

	/**
	 * Defines a response item to the emitter.
	 *
	 * @param mixed $responsePayload
	 * @return void
	 */
	public function respond($responsePayload)
	{
		$this->responsePayload = $responsePayload;
	}

	/**
	 * Retrieves any response details on the event.
	 *
	 * @return mixed
	 */
	public function getResponse()
	{
		return $this->responsePayload ?? null;
	}

	/**
	 * Cancels a response item.
	 *
	 * @return void
	 */
	public function cancelResponse()
	{
		$this->responsePayload = null;
	}
}

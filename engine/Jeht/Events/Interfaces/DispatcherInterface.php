<?php
namespace Jeht\Events\Interfaces;

use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Defines a dispatcher for events.
 */
interface DispatcherInterface extends EventDispatcherInterface
{
	/**
	 * Defines the underlying listener provider.
	 *
	 * @param \Jeht\Events\Interfaces\ListenerProviderInterface $provider
	 * @return $this
	 */
	public function setListenerProvider(ListenerProviderInterface $provider);

	/**
	 * Provide all relevant listeners with an event to process.
	 *
	 * @param object $event The object to process.
	 * @return object The Event that was passed, now modified by listeners.
	 */
	public function dispatch(object $event);

	/**
	 * Register an event listener for the given event instance.
	 *
	 * @param string $event
	 * @param string $listener
	 * @return void
	 */
	public function listen(string $event, string $listener);
}


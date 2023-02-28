<?php
namespace Jeht\Events;

use Jeht\Events\Interfaces\DispatcherInterface;
use Jeht\Events\Interfaces\EventInterface;
use Jeht\Events\Interfaces\ListenerInterface;
use Jeht\Events\Interfaces\EventListenerInterface;
use Jeht\Events\Interfaces\ListenerProviderInterface;
use Jeht\Events\Exceptions\EventListenerException;
use Jeht\Interfaces\Ground\Application;

/**
 * Defines a dispatcher for events.
 */
class Dispatcher implements DispatcherInterface
{
	/**
	 * @var \Jeht\Interfaces\Ground\Application
	 */
	protected $app;

	/**
	 * @var \Jeht\Events\Interfaces\ListenerProviderInterface
	 */
	protected $provider;

	/**
	 * Create a new Dispatcher instance.
	 *
	 * @param \Jeht\Interfaces\Ground\Application $app
	 * @return void
	 */
	public function __construct(Application $app)
	{
		$this->app = $app;
	}

	/**
	 * Defines the underlying listener provider.
	 *
	 * @param \Jeht\Events\Interfaces\ListenerProviderInterface $provider
	 * @return $this
	 */
	public function setListenerProvider(ListenerProviderInterface $provider)
	{
		$this->provider = $provider;
		//
		return $this;
	}

	/**
	 * Provide all relevant listeners with an event to process.
	 *
	 * @param object $event The object to process.
	 * @return object The Event that was passed, now modified by listeners.
	 */
	public function dispatch(object $event)
	{
		if (! $this->provider) {
			return $event;
		}
		//
		if ($listeners = $this->provider->getListenersForEvent($event)) {
			foreach ($listeners as $listener) {
				$this->invokeListener($listener, $event);
			}
		}
		//
		return $event;
	}

	/**
	 * Invoke the given listener with the given event.
	 *
	 * @param object $listener
	 * @param object $event
	 * @return void
	 */
	protected function invokeListener(object $listener, object $event)
	{
		if ($listener instanceof EventListenerInterface && $event instanceof EventInterface) {
			$listener->handle($event);
		} elseif ($listener instanceof ListenerInterface) {
			$listener->handle($event);
		} elseif ($listener instanceof Closure) {
			$listener($event);
		} elseif (method_exists($listener, 'handle')) {
			$listener->handle($event);
		} else {
			$message = 'Listener is unable to handle a ' . get_class($event) . ' event.'
				. ' Make sure your listener class implements the handle() method. ';
			//
			throw new EventListenerException($message);
		}
	}

	/**
	 * Register an event listener for the given event instance.
	 *
	 * @param object $event
	 * @param object $listener
	 * @return void
	 */
	public function listen(string $event, string $listener)
	{
		if ($this->provider) {
			$this->provider->addListener($listener, $event);
		}
		//
		return $this;
	}

}


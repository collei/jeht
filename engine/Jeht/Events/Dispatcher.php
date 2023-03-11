<?php
namespace Jeht\Events;

use Jeht\Events\Interfaces\DispatcherInterface;
use Jeht\Events\Interfaces\EventInterface;
use Jeht\Events\Interfaces\ListenerInterface;
use Jeht\Events\Interfaces\EventListenerInterface;
use Jeht\Events\Interfaces\ListenerProviderInterface;
use Jeht\Events\Exceptions\EventListenerException;
use Jeht\Ground\Interfaces\Application;
use Jeht\Support\Reflector;

/**
 * Defines a dispatcher for events.
 */
class Dispatcher implements DispatcherInterface
{
	/**
	 * @var \Jeht\Ground\Interfaces\Application
	 */
	protected $app;

	/**
	 * @var \Jeht\Events\Interfaces\ListenerProviderInterface
	 */
	protected $provider;

	/**
	 * Create a new Dispatcher instance.
	 *
	 * @param \Jeht\Ground\Interfaces\Application $app
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
	 * @param string|object $listener
	 * @param object $event
	 * @return void
	 */
	protected function invokeListener($listener, object $event)
	{
		[$listener, $argumentTypes] = $this->resolveListener($listener);
		//
		if (!$this->eventIsOfOneOfTypes($event, $argumentTypes)) {
			return;
		}
		//
		if ($listener instanceof Closure) {
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
	 * Checks if the given event belongs to the list of clases/interfaces.
	 *
	 * @param object $event
	 * @param array $argumentTypes
	 * @return bool
	 */
	protected function eventIsOfOneOfTypes(object $event, $argumentTypes)
	{
		$class = get_class($event);
		$interfaces = class_implements($event);
		//
		return in_array($class, $argumentTypes)
			|| count(array_intersect($interfaces, $argumentTypes)) > 0;
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

	/**
	 * Register an event subscriber with the dispatcher.
	 *
	 * @param  object|string  $subscriber
	 * @return void
	 */
	public function subscribe($subscriber)
	{
		$subscriber = $this->resolveSubscriber($subscriber);
		//
		$events = $subscriber->subscribe($this);
		//
		if (is_array($events)) {
			foreach ($events as $event => $listeners) {
				foreach (Arr::wrap($listeners) as $listener) {
					if (is_string($listener) && method_exists($subscriber, $listener)) {
						$this->listen($event, [get_class($subscriber), $listener]);
						//
						continue;
					}
					//
					$this->listen($event, $listener);
				}
			}
		}
	}

	/**
	 * Resolve the listener instance.
	 *
	 * @param  object|string  $listener
	 * @return array
	 */
	protected function resolveListener($listener)
	{
		if (is_string($listener) && class_exists($listener)) {
			$listener = $this->app->make($listener);
		}
		//
		$parameterTypes = ($listener instanceof Closure)
			? Reflector::getFirstParameterTypeClassNames($listener)
			: Reflector::getFirstParameterTypeClassNames([$listener, 'handle']);
		//
		return array($listener, $parameterTypes);
	}

	/**
	 * Resolve the subscriber instance.
	 *
	 * @param  object|string  $subscriber
	 * @return mixed
	 */
	protected function resolveSubscriber($subscriber)
	{
		if (is_string($subscriber) && class_exists($subscriber)) {
			return $this->app->make($subscriber);
		}
		//
		return $subscriber;
	}

}


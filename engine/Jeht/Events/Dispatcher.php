<?php
namespace Jeht\Events;

use Jeht\Events\Interfaces\DispatcherInterface;
use Jeht\Ground\Application;

/**
 * Defines a dispatcher for events.
 */
class Dispatcher implements DispatcherInterface
{
	protected $app;

	public function __construct(Application $app)
	{
		$this->app = $app;
	}

	/**
	 * Provide all relevant listeners with an event to process.
	 *
	 * @param object $event The object to process.
	 * @return object The Event that was passed, now modified by listeners.
	 */
	public function dispatch(object $event)
	{
//du(get_class($event), $event->details());
	}


	public function listen(object $event, $listener)
	{
//du(get_class($event), $event->details(), $listener);
	}

}


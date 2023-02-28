<?php
namespace Jeht\Events\Traits;

/**
 *	@author	Alarido	<alarido.su@gmail.com>
 *	@author	Collei Inc. <collei@collei.com.br>
 *	@since	2022-10-04
 *
 *	Trait for stoppable events.
 */
trait StoppableEventTrait
{
	/**
	 *	@var bool $stopped = false
	 */
	private $stopped = false;

	/**
	 *	Used by the Listener to determine if current event is meant to
	 *	have its propagation blocked.
	 *
	 *	@return	void
	 */
	public function stopPropagation()
	{
		$this->stopped = true;
	}

	/**
	 *	Used by the Dispatcher to determine if previous listener halted
	 *	propagation of such event.
	 *
	 *	@return	bool
	 */
	public function isPropagationStopped(): bool
	{
		return $this->stopped;
	}
}


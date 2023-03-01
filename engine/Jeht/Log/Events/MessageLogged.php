<?php
namespace Jeht\Log\Events;

use Jeht\Events\AbstractEvent;

/**
 * Event fired while the Application is Bootstraping bootstrappers
 *
 */
class MessageLogged extends AbstractEvent
{
	public function __construct($level, $message, array $context = [])
	{
		parent::__construct(compact('level','message','context'));
	}
}

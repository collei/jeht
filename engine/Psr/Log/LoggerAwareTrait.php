<?php
namespace Psr\Log;

/**
 * It can be used to implement the LoggerAwareInterface easily
 * in any class. It gives you access to $this->logger.
 */
trait LoggerAwareTrait
{
	/**
	 * @var LoggerInterface $logger
	 */
	private $logger;

	/**
	 * Sets a logger instance on the object.
	 *
	 * @param LoggerInterface $logger
	 * @return void
	 */
	public function setLogger(LoggerInterface $logger)
	{
		$this->logger = $logger;
	}
}


<?php
namespace Jeht\Cache\Interfaces;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

interface CacheManagerInterface extends CacheInterface
{
	/**
	 * Returns the underlying driver used by the manager.
	 *
	 * @return \Jeht\Cache\CacheDriverInterface
	 */
	public function driver();

	/**
	 * Defines the underlying driver used by the manager.
	 *
	 * @param \Jeht\Cache\CacheDriverInterface $driver
	 * @return void
	 */
	public function setDriver(CacheDriverInterface $driver);
}
<?php
namespace Jeht\Cache;

use Jeht\Cache\Interfaces\CacheManagerInterface;
use Jeht\Cache\Interfaces\CacheDriverInterface;
use Jeht\Cache\Drivers\DefaultCacheDriver;
use Jeht\Ground\Application;
use Jeht\Interfaces\Support\Stringable;
use Stringable as NativeStringable;

class CacheManager implements CacheManagerInterface
{
	/**
	 * @var \Jeht\Ground\Application
	 */
	protected $app;

	/**
	 * @var \Jeht\Cache\CacheDriverInterface
	 */
	protected $driver;

	/**
	 * Creates a new instance of Cache
	 *
	 * @param \Jeht\Cache\CacheDriverInterface $driver
	 * @return void
	 */
	public function __construct(Application $app)
	{
		$this->app = $app;

		$this->driver = new DefaultCacheDriver(
			$app['path.storage'].'/framework/cache/data'
		);
	}

	/**
	 * Returns the underlying driver used by the manager.
	 *
	 * @return \Jeht\Cache\CacheDriverInterface
	 */
	public function driver()
	{
		return $this->driver;
	}

	/**
	 * Defines the underlying driver used by the manager.
	 *
	 * @param \Jeht\Cache\CacheDriverInterface $driver
	 * @return void
	 */
	public function setDriver(CacheDriverInterface $driver)
	{
		$this->driver = $driver;
	}

	/**
	 * Validates the key according to PSR-6 and PSR-16 prescriptions.
	 *
	 * Key is valid if contains no characters beyond the ones matching
	 * the regex [A-Za-z0-9_.].
	 *
	 * @param \Jeht\Cache\CacheDriverInterface $driver
	 * @return void
	 */
	protected function validateKey($key)
	{
		$valid = is_string($key) || $key instanceof NativeStringable || $key instanceof Stringable;
		//
		if (!$valid) {
			return false;
		}
		//
		$key = (string)$key;
		//
		return ($key === preg_replace('/[^A-Za-z0-9_.]/', '', $key));
	}

	/**
	 * Fetches a value from the cache.
	 *
	 * @param string $key The unique key of this item in the cache.
	 * @param mixed $default Default value to return if the key does not exist.
	 *
	 * @return mixed The value of the item from the cache, or $default in case of cache miss.
	 *
	 * @throws \Psr\SimpleCache\InvalidArgumentException
	 *	MUST be thrown if the $key string is not a legal value.
	 */
	public function get($key, $default = null)
	{
		if (!$this->validateKey($key)) {
			throw new InvalidArgumentException(
				'Key must be a valid string with only alphanumeric, underscore and dots.'
			);
		}
		//
		return $this->driver->get($key, $default);
	}

	/**
	 * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
	 *
	 * @param string $key The key of the item to store.
	 * @param mixed $value The value of the item to store. Must be serializable.
	 * @param null|int|\DateInterval $ttl Optional. The TTL value of this item. If no value is sent and
	 *	the driver supports TTL then the library may set a default value
	 *	for it or let the driver take care of that.
	 *
	 * @return bool True on success and false on failure.
	 *
	 * @throws \Psr\SimpleCache\InvalidArgumentException
	 *	MUST be thrown if the $key string is not a legal value.
	 */
	public function set($key, $value, $ttl = null)
	{
		if (!$this->validateKey($key)) {
			throw new InvalidArgumentException(
				'Key must be a valid string with only alphanumeric, underscore and dots.'
			);
		}
		//
		if (func_num_args() === 2) {
			$this->driver->set($key, $value);
		} else {
			$this->driver->set($key, $value, $ttl);
		}
	}

	/**
	 * Delete an item from the cache by its unique key.
	 *
	 * @param string $key The unique cache key of the item to delete.
	 *
	 * @return bool True if the item was successfully removed. False if there was an error.
	 *
	 * @throws \Psr\SimpleCache\InvalidArgumentException
	 *	MUST be thrown if the $key string is not a legal value.
	 */
	public function delete($key)
	{
		if (!$this->validateKey($key)) {
			throw new InvalidArgumentException(
				'Key must be a valid string with only alphanumeric, underscore and dots.'
			);
		}
		//
		return $this->driver->delete($key);
	}

	/**
	 * Wipes clean the entire cache's keys.
	 *
	 * @return bool True on success and false on failure.
	 */
	public function clear()
	{
		return $this->driver->clear();
	}

	/**
	 * Obtains multiple cache items by their unique keys.
	 *
	 * @param iterable $keys A list of keys that can obtained in a single operation.
	 * @param mixed	$default Default value to return for keys that do not exist.
	 *
	 * @return iterable A list of key => value pairs. Cache keys that do not exist or are stale will have $default as value.
	 *
	 * @throws \Psr\SimpleCache\InvalidArgumentException
	 *	MUST be thrown if $keys is neither an array nor a Traversable,
	 *	or if any of the $keys are not a legal value.
	 */
	public function getMultiple($keys, $default = null)
	{
		if (!is_iterable($keys)) {
			throw new InvalidArgumentException('Keys must be an array or a Traversable.');
		}
		//
		$values = [];
		//
		foreach ($keys as $key) {
			$values[$key] = $this->get($key, $default);
		}
		//
		return $values;
	}

	/**
	 * Persists a set of key => value pairs in the cache, with an optional TTL.
	 *
	 * @param iterable $values A list of key => value pairs for a multiple-set operation.
	 * @param null|int|\DateInterval $ttl Optional. The TTL value of this item. If no value is sent and
	 *	the driver supports TTL then the library may set a default value
	 *	for it or let the driver take care of that.
	 *
	 * @return bool True on success and false on failure.
	 *
	 * @throws \Psr\SimpleCache\InvalidArgumentException
	 *	MUST be thrown if $values is neither an array nor a Traversable,
	 *	or if any of the $values are not a legal value.
	 */
	public function setMultiple($values, $ttl = null)
	{
		if (!is_iterable($values)) {
			throw new InvalidArgumentException('Values must be an associative array.');
		}
		//
		if (func_num_args() === 1) {
			foreach ($values as $key => $value) {
				$this->driver->set($key, $value);
			}
		} else {
			foreach ($values as $key => $value) {
				$this->driver->set($key, $value, $ttl);
			}
		}
	}

	/**
	 * Deletes multiple cache items in a single operation.
	 *
	 * @param iterable $keys A list of string-based keys to be deleted.
	 *
	 * @return bool True if the items were successfully removed. False if there was an error.
	 *
	 * @throws \Psr\SimpleCache\InvalidArgumentException
	 *	MUST be thrown if $keys is neither an array nor a Traversable,
	 *	or if any of the $keys are not a legal value.
	 */
	public function deleteMultiple($keys)
	{
		if (!is_iterable($keys)) {
			throw new InvalidArgumentException('Keys must be an array or a Traversable.');
		}
		//
		$result = true;
		//
		foreach ($keys as $key) {
			$result = $result && $this->delete($key);
		}
		//
		return $result;
	}

	/**
	 * Determines whether an item is present in the cache.
	 *
	 * NOTE: It is recommended that has() is only to be used for cache warming type purposes
	 * and not to be used within your live applications operations for get/set, as this method
	 * is subject to a race condition where your has() will return true and immediately after,
	 * another script can remove it, making the state of your app out of date.
	 *
	 * @param string $key The cache item key.
	 *
	 * @return bool
	 *
	 * @throws \Psr\SimpleCache\InvalidArgumentException
	 *	MUST be thrown if the $key string is not a legal value.
	 */
	public function has($key)
	{
		if (!$this->validateKey($key)) {
			throw new InvalidArgumentException(
				'Key must be a valid string with only alphanumeric, underscore and dots.'
			);
		}
		//
		return $this->driver->has($key);
	}

}



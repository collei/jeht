<?php
namespace Jeht\Cache\Drivers;

use Jeht\Cache\Interfaces\CacheDriverInterface;
use Jeht\Support\Traits\InteractsWithTime;
use Jeht\Support\Str;
use Jeht\Filesystem\Folder;
use DateTime;
use DateTimeInterface;

class DefaultCacheDriver implements CacheDriverInterface
{
	use InteractsWithTime;

	/**
	 * @var int (3600 seconds = 60 minutes = a full hour)
	 */
	public const DEFAULT_TTL = 3600;

	/**
	 * @var string
	 */
	protected $folder;

	/**
	 * @var array
	 */
	protected $items;

	/**
	 * @var bool
	 */
	protected $connected = false;

	/**
	 * @var int
	 */
	protected $defaultTtl = 0;

	/**
	 * Defines the default ttl, in seconds.
	 *
	 * @param int $seconds
	 * @return void
	 */
	public function __construct(string $folder)
	{
		$this->folder = $folder;
		//
		$this->loadAllKeys();
		//
		$this->defaultTtl = self::DEFAULT_TTL;
	}

	/**
	 * Defines the default ttl, in seconds.
	 *
	 * @param int $seconds
	 * @return void
	 */
	public function setDefaultTtl(int $seconds)
	{
		$this->defaultTtl = abs($seconds);
	}

	/**
	 * Defines the default ttl, in terms of a future date.
	 *
	 * @param \DateTimeInterface $time
	 * @return void
	 */
	public function setDefaultTtlAsFutureDateTime(DateTimeInterface $time)
	{
		$seconds = $this->secondsUntil($time);
		//
		$this->setDefaultTtl($seconds);
	}

	/**
	 * Returns the default ttl in seconds.
	 *
	 * @return int
	 */
	public function getDefaultTtl()
	{
		return $this->defaultTtl;
	}

	/**
	 * Returns the default ttl as a resulting \DateTimeInterface from now.
	 *
	 * @return \DateTimeInterface
	 */
	public function getDefaultTtlAsFutureDateTime()
	{
		return $this->addRealSecondsTo($this->now(), $this->getDefaultTtl());
	}

	/**
	 * Establishes the underlying connection.
	 *
	 * @return void
	 */
	public function connect()
	{
		if (is_dir($folder)) {
			$this->connected = true;
		} else {
			$this->connected = mkdir($folder, 0777, true);
		}
	}

	/**
	 * Returns whether the underlying connection is active or not.
	 *
	 * @return bool
	 */
	public function isConnected()
	{
		return $this->connected;
	}

	/**
	 * Closes the underlying connection.
	 *
	 * @return void
	 */
	public function close()
	{
		$this->connected = false;
	}

	/**
	 * Tells if the given file exists.
	 *
	 * @param string $fileName
	 * @return bool
	 */
	protected function hasFile(string $fileName)
	{
		$cached = $this->items[$key] ?? null;
		//
		return (
			$cached && is_file($cached) && is_readable($cached)
		);
	}

	/**
	 * Loads all keys from the cache files, if any.
	 *
	 * @return bool
	 */
	protected function loadAllKeys()
	{
		$this->items = [];
		$count = 0;
		//
		$files = Folder::for($this->folder)->files()->get();
		//
		foreach ($files as $file) {
			if ($content = $file->getContents()) {
				$item = unserialize($content);
				//
				if (is_array($item)) {
					$this->items[$item['key']] = $file->getPath();
					//
					++$count;
				}
			}
		}
		//
		return $count === count($files);
	}

	/**
	 * Loads the cached content from the file, if any.
	 *
	 * @param string $fileName
	 * @return string|false
	 */
	protected function loadFromFile(string $fileName)
	{
		$cached = $this->items[$key] ?? null;
		//
		if ($cached && is_file($cached) && is_readable($cached)) {
			if (false !== ($content = file_get_contents($cached))) {
				return unserialize($content);
			}
		}
		//
		return false;
	}

	/**
	 * Save the cache content as a file of serialized data.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param \DateTimeInterface $time
	 * @return bool
	 */
	protected function saveToFile(string $key, $value, DateTimeInterface $time)
	{
		$cached = $this->items[$key] ?? null;
		//
		if (!$cached) {
			$cached = $this->folder . DIRECTORY_SEPARATOR . Str::random(32);
			//
			$this->items[$key] = $cached;
		}
		//
		$content = compact('key','time','value');
		//
		return false !== file_put_contents($cached, serialize($content));
	}

	/**
	 * Removes the given cache file.
	 *
	 * @param string $fileName
	 * @return bool
	 */
	protected function removeFile(string $key)
	{
		if ($cached = $this->items[$key] ?? null) {
			return @unlink($cached);
		}
		//
		return false;
	}

	/**
	 * Removes all cache files.
	 *
	 * @return bool
	 */
	protected function removeAllFiles()
	{
		$result = true;
		//
		foreach ($this->items as $key => $cached) {
			$result = $result && @unlink($cached);
		}
		//
		$this->items = [];
		//
		return $result;
	}

	/**
	 * Fetches a value from the cache.
	 *
	 * @param string $key The unique key of this item in the cache.
	 * @param mixed $default Default value to return if the key does not exist.
	 *
	 * @return mixed The value of the item from the cache, or $default in case of cache miss.
	 */
	public function get($key, $default = null)
	{
		if ($item = $this->loadFile($key)) {
			if ($item['time'] <= $this->now()) {
				return $item['value'];
			}
			//
			$this->removeFile($key);
			//
			return $default;
		}
		//
		return $default;
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
	 */
	public function set($key, $value, $ttl = null)
	{
		if ($ttl instanceof DateInterval) {
			$ttl = $this->parseDateInterval($ttl);
		} elseif (is_int($ttl)) {
			$ttl = $this->addRealSecondsTo($this->now(), $ttl);
		} else {
			$ttl = $this->getDefaultTtlAsFutureDateTime();
		}
		//
		return $this->saveToFile($key, $value, $ttl);
	}

	/**
	 * Persists data in the cache, uniquely referenced by a key, forever.
	 *
	 * @param string $key The key of the item to store.
	 * @param mixed $value The value of the item to store. Must be serializable.
	 *
	 * @return bool True on success and false on failure.
	 */
	public function setForever($key, $value)
	{
		return $this->saveToFile($key, $value, $this->forever());
	}

	/**
	 * Delete an item from the cache by its unique key.
	 *
	 * @param string $key The unique cache key of the item to delete.
	 *
	 * @return bool True if the item was successfully removed. False if there was an error.
	 */
	public function delete($key)
	{
		$this->removeFile($key);
	}

	/**
	 * Wipes clean the entire cache's keys.
	 *
	 * @return bool True on success and false on failure.
	 */
	public function clear()
	{
		$this->removeAllFiles();
	}

	/**
	 * Determines whether an item is present in the cache.
	 *
	 * @param string $key The cache item key.
	 *
	 * @return bool
	 */
	public function has($key)
	{
		return $this->hasFile($key);
	}

}


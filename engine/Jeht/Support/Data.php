<?php
namespace Jeht\Support;

use ArrayAccess;

/**
 * Arranges some of Laravel helpers into a meaningful helper static class 
 *
 * Obtained from rappasoft/laravel-helpers
 * @link https://github.com/rappasoft/laravel-helpers/blob/master/src/arrays.php
 * @link https://github.com/rappasoft/laravel-helpers
 *
 */
abstract class Data
{
	/**
	 * Determine whether the given value is array accessible.
	 *
	 * @param mixed value
	 * @return bool
	 */
	public static function isArrayAccessible($value)
	{
		return is_array($value) || $value instanceof ArrayAccess;
	}

	/**
	 * Determine if the given key exists in the provided array.
	 *
	 * @param  \ArrayAccess|array  $array
	 * @param  string|int  $key
	 * @return bool
	 */
	public static function keyExists($array, $key): bool
	{
		if ($array instanceof ArrayAccess) {
			return $array->offsetExists($key);
		}
		//
		return array_key_exists($key, $array);
	}

	/**
	 * Fill in data where it's missing.
	 *
	 * @param  mixed  $target
	 * @param  string|array  $key
	 * @param  mixed  $value
	 * @return mixed
	 */
	public static function fill(&$target, $key, $value)
	{
		return self::set($target, $key, $value, false);
	}

	/**
	 * Get an item from an array or object using "dot" notation.
	 *
	 * @param  mixed  $target
	 * @param  string|array|int|null  $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	public static function get($target, $key, $default = null)
	{
		if (is_null($key)) {
			return $target;
		}
		//
		$key = is_array($key) ? $key : explode('.', $key);
		//
		foreach ($key as $i => $segment) {
			unset($key[$i]);
			//
			if (is_null($segment)) {
				return $target;
			}
			//
			if ($segment === '*') {
				if (! is_array($target)) {
					return value($default);
				}
				//
				$result = [];
				//
				foreach ($target as $item) {
					$result[] = self::get($item, $key);
				}
				//
				return in_array('*', $key) ? array_collapse($result) : $result;
			}
			//
			if (self::isArrayAccessible($target) && self::keyExists($target, $segment)) {
				$target = $target[$segment];
			} elseif (is_object($target) && isset($target->{$segment})) {
				$target = $target->{$segment};
			} else {
				return value($default);
			}
		}
		//
		return $target;
	}

	/**
	 * Set an item on an array or object using dot notation.
	 *
	 * @param  mixed  $target
	 * @param  string|array  $key
	 * @param  mixed  $value
	 * @param  bool  $overwrite
	 *
	 * @return mixed
	 */
	public static function set(&$target, $key, $value, bool $overwrite = true)
	{
		$segments = is_array($key) ? $key : explode('.', $key);
		//
		if (($segment = array_shift($segments)) === '*') {
			if (! self::isArrayAccessible($target)) {
				$target = [];
			}
			//
			if ($segments) {
				foreach ($target as &$inner) {
					self::set($inner, $segments, $value, $overwrite);
				}
			} elseif ($overwrite) {
				foreach ($target as &$inner) {
					$inner = $value;
				}
			}
		} elseif (self::isArrayAccessible($target)) {
			if ($segments) {
				if (! self::keyExists($target, $segment)) {
					$target[$segment] = [];
				}
				//
				self::set($target[$segment], $segments, $value, $overwrite);
			} elseif ($overwrite || ! self::keyExists($target, $segment)) {
				$target[$segment] = $value;
			}
		} elseif (is_object($target)) {
			if ($segments) {
				if (! isset($target->{$segment})) {
					$target->{$segment} = [];
				}
				//
				self::set($target->{$segment}, $segments, $value, $overwrite);
			} elseif ($overwrite || ! isset($target->{$segment})) {
				$target->{$segment} = $value;
			}
		} else {
			$target = [];
			//
			if ($segments) {
				self::set($target[$segment], $segments, $value, $overwrite);
			} elseif ($overwrite) {
				$target[$segment] = $value;
			}
		}
		//
		return $target;
	}
}


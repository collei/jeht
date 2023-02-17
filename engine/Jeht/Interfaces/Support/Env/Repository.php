<?php
namespace Jeht\Interfaces\Support\Env;

interface Repository
{
	/**
	 * Determine if the given configuration value exists.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function has($key);

	/**
	 * Get the specified configuration value.
	 *
	 * @param  array|string  $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	public function get($key, $default = null);

	/**
	 * Get all of the configuration items for the application.
	 *
	 * @return array
	 */
	public function all();
}



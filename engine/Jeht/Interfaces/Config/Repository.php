<?php
namespace Jeht\Interfaces\Config;

/**
 * Obtained from Laravel \Illuminate\Contracts\Config
 * @link https://laravel.com/api/8.x/Illuminate/Contracts/Config/Repository.html
 * @link https://github.com/laravel/framework/blob/8.x/src/Illuminate/Contracts/Config/Repository.php
 *
 */
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

	/**
	 * Set a given configuration value.
	 *
	 * @param  array|string  $key
	 * @param  mixed  $value
	 * @return void
	 */
	public function set($key, $value = null);

	/**
	 * Prepend a value onto an array configuration value.
	 *
	 * @param  string  $key
	 * @param  mixed  $value
	 * @return void
	 */
	public function prepend($key, $value);

	/**
	 * Push a value onto an array configuration value.
	 *
	 * @param  string  $key
	 * @param  mixed  $value
	 * @return void
	 */
	public function push($key, $value);
}



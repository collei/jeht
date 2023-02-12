<?php
namespace Jeht\Interfaces\Ground;

/**
 * Adapted from Laravel \Illuminate\Contracts\Foundation\CachesConfiguration
 * @link https://laravel.com/api/8.x/Illuminate/Contracts/Foundation/CachesConfiguration.html
 * @link https://github.com/laravel/framework/blob/8.x/src/Illuminate/Contracts/Foundation/CachesConfiguration.php
 *
 */
interface CachesConfiguration
{
	/**
	 * Determine if the application configuration is cached.
	 *
	 * @return bool
	 */
	public function configurationIsCached();

	/**
	 * Get the path to the configuration cache file.
	 *
	 * @return string
	 */
	public function getCachedConfigPath();

	/**
	 * Get the path to the cached services.php file.
	 *
	 * @return string
	 */
	public function getCachedServicesPath();
}


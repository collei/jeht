<?php
namespace Jeht\Ground\Interfaces;

/**
 * Adapted from Laravel \Illuminate\Contracts\Foundation\CachesRoutes
 * @link https://laravel.com/api/8.x/Illuminate/Contracts/Foundation/CachesRoutes.html
 * @link https://github.com/laravel/framework/blob/8.x/src/Illuminate/Contracts/Foundation/CachesRoutes.php
 *
 */
interface CachesRoutes
{
	/**
	 * Determine if the application routes are cached.
	 *
	 * @return bool
	 */
	public function routesAreCached();

	/**
	 * Get the path to the routes cache file.
	 *
	 * @return string
	 */
	public function getCachedRoutesPath();
}


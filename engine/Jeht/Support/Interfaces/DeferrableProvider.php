<?php
namespace Jeht\Support\Interfaces;

/**
 * adapted from Laravel's Illuminate\Contracts\Support\DeferrableProvider
 * @link https://laravel.com/api/8.x/Illuminate/Contracts/Support/DeferrableProvider.html
 * @link https://github.com/laravel/framework/blob/8.x/src/Illuminate/Contracts/Support/DeferrableProvider.php
 *
 */
interface DeferrableProvider
{
	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides();
}


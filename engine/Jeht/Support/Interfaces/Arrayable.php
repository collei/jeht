<?php
namespace Jeht\Support\Interfaces;

/**
 * From Laravel's Illuminate\Contracts\Support\Arrayable
 * @link https://laravel.com/api/8.x/Illuminate/Contracts/Support/Arrayable.html
 * @link https://github.com/laravel/framework/blob/8.x/src/Illuminate/Contracts/Support/Arrayable.php
 */
interface Arrayable
{
	/**
	 * Get the instance as an array.
	 *
	 * @return array
	 */
	public function toArray();
}


<?php
namespace Jeht\Support\Interfaces;

/**
 * From Laravel's Illuminate\Contracts\Support\Jsonable
 * @link https://laravel.com/api/8.x/Illuminate/Contracts/Support/Jsonable.html
 * @link https://github.com/laravel/framework/blob/8.x/src/Illuminate/Contracts/Support/Jsonable.php
 */
interface Jsonable
{
	/**
	 * Convert the object to its JSON representation.
	 *
	 * @param  int  $options
	 * @return string
	 */
	public function toJson($options = 0);
}


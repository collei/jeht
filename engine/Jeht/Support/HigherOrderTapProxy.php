<?php
namespace Jeht\Support;

/**
 * Adapetd from Laravel's Illuminate\Support\HigherOrderTapProxy.
 * @link https://laravel.com/api/9.x/Illuminate/Support/HigherOrderTapProxy.html
 * @link https://github.com/laravel/framework/blob/9.x/src/Illuminate/Support/HigherOrderTapProxy.php
 * @link https://github.com/laravel/framework/blob/10.x/LICENSE.md
 *
 */
class HigherOrderTapProxy
{
	/**
	 * The target being tapped.
	 *
	 * @var mixed
	 */
	public $target;

	/**
	 * Create a new tap proxy instance.
	 *
	 * @param  mixed  $target
	 * @return void
	 */
	public function __construct($target)
	{
		$this->target = $target;
	}

	/**
	 * Dynamically pass method calls to the target.
	 *
	 * @param  string  $method
	 * @param  array  $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		$this->target->{$method}(...$parameters);

		return $this->target;
	}
}


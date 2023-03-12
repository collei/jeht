<?php
namespace Jeht\Support\Traits;

trait Withable
{
	/**
	 * Returns the object passed to it. useful for chaining.
	 * If a Closure is passed at the second argument, it is called
	 * with the first argument as its only argument.
	 *
	 * @param	object		$anything
	 * @param	\Closure	$func = null
	 * @return	$anything
	 */
	public function with(object $anything, Closure $func = null)
	{
		if (! is_null($func)) {
			$func($anything);
		}
		//
		return $anything;
	}
}
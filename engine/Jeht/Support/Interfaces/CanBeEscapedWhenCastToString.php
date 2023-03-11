<?php
namespace Jeht\Support\Interfaces;

/**
 * Fem Laravel's Illuminate\Contracts\Support
 * @link https://laravel.com/api/8.x/Illuminate/Contracts/Support/CanBeEscapedWhenCastToString.html
 * @link https://github.com/laravel/framework/blob/8.x/src/Illuminate/Contracts/Support/CanBeEscapedWhenCastToString.php
 */
interface CanBeEscapedWhenCastToString
{
	/**
	 * Indicate that the object's string representation should be escaped when __toString is invoked.
	 *
	 * @param  bool  $escape
	 * @return $this
	 */
	public function escapeWhenCastingToString($escape = true);
}


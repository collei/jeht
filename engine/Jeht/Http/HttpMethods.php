<?php
namespace Jeht\Http;

final class HttpMethods
{
	/**
	 * @var string[]
	 */
	public const HTTP_METHODS = [
		'GET','POST','PUT','PATCH','OPTIONS','HEAD','DELETE'
	];

	/**
	 * Validates a string against HTTP methods
	 *
	 * @param string $method
	 * @return bool
	 */
	public static function validate(string $method)
	{
		return in_array(strtoupper($method), self::HTTP_METHODS, true);
	} 

	/**
	 * Validates an array of string against HTTP methods.
	 * Returns null if all $methods are valid, or an array with all invalid.
	 *
	 * @param array $methods
	 * @return array|null
	 */
	public static function validateAll(array $methods, array &$invalid = null)
	{
		$invalid = [];
		//
		foreach ($methods as $method) {
			if (! self::validate($method)) {
				$invalid[] = $method;
			}
		}
		//
		return empty($invalid) ? null : $invalid;
	}
	
}


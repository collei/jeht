<?php
namespace Jeht\Support\Traits;

use BadMethodCallException;
use Error;

/**
 * from Laravel's Illuminate\Support\Traits\ForwardsCalls
 * @link https://laravel.com/api/8.x/Illuminate/Support/Traits/ForwardsCalls.html
 * @link https://github.com/laravel/framework/blob/8.x/src/Illuminate/Support/Traits/ForwardsCalls.php
 *
 */
trait ForwardsCalls
{
	/**
	 * Forward a method call to the given object.
	 *
	 * @param  mixed  $object
	 * @param  string  $method
	 * @param  array  $parameters
	 * @return mixed
	 *
	 * @throws \BadMethodCallException
	 */
	protected function forwardCallTo($object, $method, $parameters)
	{
		try {
			return $object->{$method}(...$parameters);
		} catch (Error|BadMethodCallException $e) {
			$pattern = '~^Call to undefined method (?P<class>[^:]+)::(?P<method>[^\(]+)\(\)$~';
			//
			if (! preg_match($pattern, $e->getMessage(), $matches)) {
				throw $e;
			}
			//
			if ($matches['class'] != get_class($object) ||
				$matches['method'] != $method) {
				throw $e;
			}
			//
			static::throwBadMethodCallException($method);
		}
	}

	/**
	 * Forward a method call to the given object, returning $this if the forwarded call returned itself.
	 *
	 * @param  mixed  $object
	 * @param  string  $method
	 * @param  array  $parameters
	 * @return mixed
	 *
	 * @throws \BadMethodCallException
	 */
	protected function forwardDecoratedCallTo($object, $method, $parameters)
	{
		$result = $this->forwardCallTo($object, $method, $parameters);
		//
		if ($result === $object) {
			return $this;
		}
		//
		return $result;
	}

	/**
	 * Throw a bad method call exception for the given method.
	 *
	 * @param  string  $method
	 * @return void
	 *
	 * @throws \BadMethodCallException
	 */
	protected static function throwBadMethodCallException($method)
	{
		throw new BadMethodCallException(sprintf(
			'Call to undefined method %s::%s()', static::class, $method
		));
	}
}


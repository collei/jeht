<?php
namespace Jeht\Container;

use Closure;
use ReflectionNamedType;

/**
 * Utilities for the container.
 *
 * Obtained from Laravel's \Illuminate\Container\Util sources
 * @link https://laravel.com/api/8.x/Illuminate/Container/Util.html
 * 
 * Note: it works with PHP version >= 7.1.0 because of the ReflectionNamedType
 * class being available henceforth.
 * @link https://www.php.net/manual/pt_BR/class.reflectionnamedtype.php
 */
class Util
{
	/**
	 * If the given value is not an array and not null, wrap it in one.
	 *
	 * @param  mixed  $value
	 * @return array
	 */
	public static function arrayWrap($value)
	{
		if (is_null($value)) {
			return [];
		}
		//
		return is_array($value) ? $value : [$value];
	}

	/**
	 * Return the default value of the given value.
	 *
	 * @param  mixed  $value
	 * @return mixed
	 */
	public static function unwrapIfClosure($value)
	{
		return $value instanceof Closure ? $value() : $value;
	}

	/**
	 * Get the class name of the given parameter's type, if possible.
	 *
	 * @param  \ReflectionParameter  $parameter
	 * @return string|null
	 */
	public static function getParameterClassName($parameter)
	{
		$type = $parameter->getType();
		//
		if ($className = self::getClassNameWhileAutoloading($type)) {
			return $className;
		}
		//
		if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
			return;
		}
		//
		$name = $type->getName();
		//
		if (! is_null($class = $parameter->getDeclaringClass())) {
			if ($name === 'self') {
				return $class->getName();
			}
			//
			if ($name === 'parent' && $parent = $class->getParentClass()) {
				return $parent->getName();
			}
		}
		//
		return $name;
	}

	/**
	 * Returns the class name if it does exist. It triggers autoloading.
	 * Returns null if not found anywhere.
	 *
	 * @param	\ReflectionNamedType|\Stringable|string	$type
	 * @return	string|null
	 */
	public static function getClassNameWhileAutoloading($type)
	{
		$className = '' . $type . '';
		//
		if (class_exists($className, true) || interface_exists($className, true)) {
			return $className;
		}
		//
		return null;
	}

}



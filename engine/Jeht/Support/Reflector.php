<?php
namespace Jeht\Support;

use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;

/**
 * from laravel's Illuminate\Support\Reflector
 * @link https://laravel.com/api/8.x/Illuminate/Support/Reflector.html
 * @link https://github.com/laravel/framework/blob/8.x/src/Illuminate/Support/Reflector.php
 *
 */
class Reflector
{
	/**
	 * This is a PHP 7.4 compatible implementation of is_callable.
	 *
	 * @param  mixed  $var
	 * @param  bool  $syntaxOnly
	 * @return bool
	 */
	public static function isCallable($var, $syntaxOnly = false)
	{
		if (! is_array($var)) {
			return is_callable($var, $syntaxOnly);
		}

		if ((! isset($var[0]) || ! isset($var[1])) ||
			! is_string($var[1] ?? null)) {
			return false;
		}

		if ($syntaxOnly &&
			(is_string($var[0]) || is_object($var[0])) &&
			is_string($var[1])) {
			return true;
		}

		$class = is_object($var[0]) ? get_class($var[0]) : $var[0];

		$method = $var[1];

		if (! class_exists($class)) {
			return false;
		}

		if (method_exists($class, $method)) {
			return (new ReflectionMethod($class, $method))->isPublic();
		}

		if (is_object($var[0]) && method_exists($class, '__call')) {
			return (new ReflectionMethod($class, '__call'))->isPublic();
		}

		if (! is_object($var[0]) && method_exists($class, '__callStatic')) {
			return (new ReflectionMethod($class, '__callStatic'))->isPublic();
		}

		return false;
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

		if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
			return;
		}

		return static::getTypeName($parameter, $type);
	}

	/**
	 * Get the class names of the given parameter's type, including union types.
	 *
	 * @param  \ReflectionParameter  $parameter
	 * @return array
	 */
	public static function getParameterClassNames($parameter)
	{
		$type = $parameter->getType();

		if (! $type instanceof ReflectionUnionType) {
			return array_filter([static::getParameterClassName($parameter)]);
		}

		$unionTypes = [];

		foreach ($type->getTypes() as $listedType) {
			if (! $listedType instanceof ReflectionNamedType || $listedType->isBuiltin()) {
				continue;
			}

			$unionTypes[] = static::getTypeName($parameter, $listedType);
		}

		return array_filter($unionTypes);
	}

	/**
	 * Get the class names of the first parameter's type of the given function
	 * or method, including union types.
	 *
	 * @param	string|array|callable  $function
	 * @return	array[]
	 */
	public static function getFirstParameterTypeClassNames($function)
	{
		$results = [];
		//
		if ($function = self::getReflectionFunctionFrom($function)) {
			$parameters = $function->getParameters();
			//
			if (count($parameters) > 0) {
				$results = self::getParameterClassNames($parameters[0]);
			}
		}
		//
		return $results;
	}

	/**
	 * Get a ReflectionFunction or a ReflectionMethod for a $function.
	 *
	 * @param  string|array|callable  $parameter
	 * @return \ReflectionFunction|\ReflectionMethod
	 */
	public static function getReflectionFunctionFrom($function)
	{
		if ($function instanceof Closure) {
			return new ReflectionFunction($function);
		}
		//
		if (is_string($function) && false !== strpos($function, '@')) {
			$function = explode('@', $function);
		}
		//
		if (is_array($function) && count($function) > 1) {
			return new ReflectionMethod($function[0], $function[1]);
		}
		//
		return null;
	}

	/**
	 * Get the given type's class name.
	 *
	 * @param  \ReflectionParameter  $parameter
	 * @param  \ReflectionNamedType  $type
	 * @return string
	 */
	protected static function getTypeName($parameter, $type)
	{
		$name = $type->getName();

		if (! is_null($class = $parameter->getDeclaringClass())) {
			if ($name === 'self') {
				return $class->getName();
			}

			if ($name === 'parent' && $parent = $class->getParentClass()) {
				return $parent->getName();
			}
		}

		return $name;
	}

	/**
	 * Determine if the parameter's type is a subclass of the given type.
	 *
	 * @param  \ReflectionParameter  $parameter
	 * @param  string  $className
	 * @return bool
	 */
	public static function isParameterSubclassOf($parameter, $className)
	{
		$paramClassName = static::getParameterClassName($parameter);

		return $paramClassName
			&& (class_exists($paramClassName) || interface_exists($paramClassName))
			&& (new ReflectionClass($paramClassName))->isSubclassOf($className);
	}
}


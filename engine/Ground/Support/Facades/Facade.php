<?php
namespace Ground\Support\Facades;

use RuntimeException;

abstract class Facade
{
	protected static $resolvedInstance;

	public static function getFacadeRoot()
	{
		if (self::$resolvedInstance) {
			return self::$resolvedInstance;
		}

		return static::$resolvedInstance = static::resolveInstance();
	}

	protected static function resolveInstance()
	{
		throw new RuntimeException('facade does not implement resolveInstance method.');
	}

	public static function __callStatic($method, $args)
	{
		$instance = static::getFacadeRoot();

		if (! $instance) {
			throw new RuntimeException('A facade root has not been set.');
		}

		return $instance->$method(...$args);
	}

}


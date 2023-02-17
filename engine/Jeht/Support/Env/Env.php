<?php
namespace Jeht\Support\Env;

use Jeht\Interfaces\Support\Env\Parser as EnvParserInterface;
use Jeht\Support\Str;

class Env
{
	/**
	 * @var \Jeht\Interfaces\Support\Env\Parser
	 */
	protected static $putenv = true;

	/**
	 * @var \Jeht\Interfaces\Support\Env\Repository
	 */
	protected static $repository;

	/**
	 * @var string
	 */
	protected static $path;

	/**
	 * Returns the repository. Creates it anew if needed.
	 *
	 * @return \Jeht\Interfaces\Support\Env\Repository
	 */
	protected static function getRepository()
	{
		if (is_null(static::$repository)) {
			static::$repository = new Repository(static::$path);
		}
		//
		return static::$repository;
	}

	/**
	 * Initializes the .env repository.
	 * It should be typically called by the LoadEnvironmentVariables
	 * app bootstrap class at app startup.
	 *
	 * @param string $path
	 * @return void
	 */
	public static function initialize(string $path)
	{
		static::$repository = new Repository(
			static::$path = $path
		);
	}

	/**
	 * Removes the .env repository.
	 *
	 * @return void
	 */
	public static function terminate()
	{
		static::$repository = null;
	}

	/**
	 * Returns the specified environment variable from one of
	 * the following sources, in order:
	 * - the one set by the attached EnvParserInterface
	 * - the value returned by PHP getenv()
	 * - the default value
	 *
	 * @param string $key
	 * @param mixed $default
	 * @return mixed 
	 */
	public static function get(string $key, $default = null)
	{
		return static::getRepository()->get($key, $default);
	}

}


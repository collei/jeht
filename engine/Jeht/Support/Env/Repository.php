<?php
namespace Jeht\Support\Env;

use Jeht\Support\Env\Interfaces\Parser as EnvParserInterface;
use Jeht\Support\Env\Interfaces\Repository as EnvRepositoryInterface;
use Jeht\Exceptions\Filesystem\FileNotFoundException;

class Repository implements EnvRepositoryInterface
{
	/**
	 * @var array
	 */
	protected $entries = [];

	/**
	 * Calls getenv() and returns the value, or null if the environment
	 * variable does not exist.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	protected function getenvOrNull(string $key)
	{
		if (false !== ($value = getenv($key))) {
			return $value;
		}
		//
		return;
	}

	/**
	 * Determine if the given configuration value exists.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function has($key)
	{
		return array_key_exists($key, $this->entries)
			|| null !== $this->getenvOrNull($key);
	}

	/**
	 * Get the specified configuration value.
	 *
	 * @param  array|string  $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	public function get($key, $default = null)
	{
		return $this->entries[$key] ?? $this->getenvOrNull($key) ?? $default;
	}

	/**
	 * Get all of the configuration items for the application.
	 *
	 * @return array
	 */
	public function all()
	{
		return $entries = $this->entries;
	}

	/**
	 * Loads the specified file.
	 *
	 * @param string $path
	 * @throws \Jeht\Exceptions\Filesystem\FileNotFoundException
	 */
	public function __construct(string $path = null)
	{
		if (!empty($path)) {
			try {
				$this->entries = Parser::from(file_get_contents($path))
						->parse()
						->getEntries();
			} catch (Exception $e) {
				throw new FileNotFoundException(
					"Environment file not found: [$path]."
				);
			}
		}
	}

}

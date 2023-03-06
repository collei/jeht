<?php
namespace Jeht\Support;

class Caller
{
	/**
	 * @var array
	 */
	protected $targets;

	/**
	 * @var bool
	 */
	protected $static = false;

	/**
	 * Builds a new instance.
	 *
	 * @param array $things
	 * @param bool $static = false
	 * @return void
	 */
	public function __construct(array $things, bool $static = false)
	{
		$this->targets = $things;
		$this->static = $static;
	}

	/**
	 * Configures a list of instances to act upon.
	 *
	 * @param string ...$classes
	 * @return static
	 */
	public static function forStatic(...$classes)
	{
		return new static($classes, true);
	}

	/**
	 * Configures a list of instances to act upon.
	 *
	 * @param object ...$instances
	 * @return static
	 */
	public static function for(...$instances)
	{
		return new static($instances);
	}

	/**
	 * Calls a method upon every class/instance and collects their returns.
	 *
	 * @param string $name
	 * @param array $arguments = []
	 * @return array
	 */
	public function __call(string $name, array $arguments = [])
	{
		$returns = [];
		//
		foreach ($this->targets as $target) {
			$returns[] = call_user_func_array([$target, $name], $arguments);
		}
		//
		return $returns;
	}
}


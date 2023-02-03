<?php
namespace Jeht\Interfaces\Ground;

use Jeht\Interfaces\Container\Container;

interface ApplicationInterface extends Container
{
	/**
	 * Returns the path for the given $folder
	 *
	 * @param string $name
	 * @return string|null;
	 */
	public function getFolder(string $name);

	/**
	 * Initializes the application
	 *
	 * @static
	 * @param string $name
	 * @param string $baseDir
	 * @return static
	 */
	public static function initialize(string $name, string $baseDir);

}

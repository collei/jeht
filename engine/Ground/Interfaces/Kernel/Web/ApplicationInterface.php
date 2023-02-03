<?php
namespace Ground\Interfaces\Kernel\Web;

use Ground\Interfaces\Container\Container;

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

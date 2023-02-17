<?php
namespace Jeht\Exceptions\Filesystem;

use Throwable;

/**
 * File not found
 *
 */
class FileNotFoundException extends FilesystemException
{
	/**
	 * @var string
	 */
	protected $path = null;

	/**
	 * Instantiates it.
	 *
	 * @param string $message = null
	 * @param int $code = 0
	 * @param Throwable $previous = null
	 * @param string $path = null
	 */
	public function __construct(string $message = null, int $code = 0, Throwable $previous = null, string $path = null)
	{
		$message = !is_null($message)
			? $message
			: (!is_null($path) ? "File not found: [$path]." : "File not found.");
		//
		$this->path = $path;
		//
		parent::__construct($message, $code, $previous, $path);
	}
}


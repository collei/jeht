<?php
namespace Jeht\Exceptions\Filesystem;

use RuntimeException;
use Throwable;

/**
 * Filesystem Exceptions
 *
 */
class FilesystemException extends RuntimeException
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
		$this->path = $path;
		//
		parent::__construct($message, $code, $previous);
	}
}


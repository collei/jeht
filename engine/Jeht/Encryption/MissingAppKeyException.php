<?php
namespace Jeht\Encryption;

use RuntimeException;

/**
 * Adapted from the works at illuminate/encryption package 
 * @author Taylor Otwell <taylor@laravel.com>.
 *
 * @link https://github.com/illuminate/encryption
 * @link https://github.com/illuminate/encryption/blob/master/MissingAppKeyException.php
 * @link https://github.com/illuminate/encryption/blob/master/LICENSE.md
 *
 */
class MissingAppKeyException extends RuntimeException
{
	/**
	 * Create a new exception instance.
	 *
	 * @param  string  $message
	 * @return void
	 */
	public function __construct($message = 'No application encryption key has been specified.')
	{
		parent::__construct($message);
	}
}


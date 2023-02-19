<?php
namespace Jeht\Support\Streams;

use Throwable;
use Exception;
use RuntimeException;

class StreamUtils
{
	/**
	 * Safely gets the contents of a given stream.
	 *
	 * When stream_get_contents fails, PHP normally raises a warning. This
	 * function adds an error handler that checks for errors and throws an
	 * exception instead.
	 *
	 * Implementation by guzzle/psr7 <https://github.com/guzzle/psr7>
	 * Taken from GuzzleHttp\Psr7\Utils::tryGetContents at date 2023-02-04
	 *
	 * @link https://github.com/guzzle/psr7/blob/ddb46aeea6423f504136cff6989ae39064301eef/src/Utils.php#L400 
	 *
	 * @param resource $stream
	 * @throws RuntimeException if the stream cannot be read
	 */
	public static function tryGetContents($stream)
	{
		$exception = null;
		//
		set_error_handler(static function (int $errno, string $errstr) use (&$exception): bool {
			$exception = new RuntimeException(
				"Unable to read stream contents: {$errstr}."
			);
			//
			return true;
		});
		//
		try {
			$contents = stream_get_contents($stream);
			//
			if (false === $contents) {
				$exception = new RuntimeException('Unable to read stream contents.');
			}
		}
		catch (Throwable $t) {
			$message = $t->getMessage();
			//
			$exception = new RuntimeException("Unable to read stream contents: {$message}.");
		}
		//
		restore_error_handler();
		//
		if ($exception) {
			throw $exception;
		}
		//
		return $contents;
	}
	
}


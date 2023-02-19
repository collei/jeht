<?php
namespace Jeht\Support\Streams;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\StreamFactoryInterface;

class StreamFactory implements StreamFactoryInterface
{
	/**
	 * Create a new stream from a string.
	 *
	 * @param string $content String content with which to populate the stream.
	 * @return \Psr\Http\Message\StreamInterface
	 */
	public function createStream(string $content = ''): StreamInterface
	{
		$handle = fopen('php://memory', 'w+');
		//
		fwrite($handle, $content);
		//
		return new Stream($handle, strlen($content));
	}

	/**
	 * Create a stream from an existing file.
	 *
	 * The file MUST be opened using the given mode, which may be any mode
	 * supported by the `fopen` function.
	 *
	 * The `$filename` MAY be any string supported by `fopen()`.
	 *
	 * @param string $filename The filename or stream URI to use as basis of stream.
	 * @param string $mode The mode with which to open the underlying filename/stream.
	 * @return \Psr\Http\Message\StreamInterface
	 * @throws \RuntimeException If the file cannot be opened.
	 * @throws \InvalidArgumentException If the mode is invalid.
	 */
	public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
	{
		if (! Stream::isValidMode($mode)) {
			throw new InvalidArgumentException("Invalid fopen() mode: [$mode].");
		}
		//
		if (! is_readable($filename)) {
			throw new RuntimeException("File [$filename] cannot be open.");
		}
		//
		try {
			$handle = @fopen($filename, $mode);
		} catch (Throwable $t) {
			throw new RuntimeException("File [$filename] could not be open.");
		}
		//
		return new Stream($handle, filesize($filename));
	}

	/**
	 * Create a new stream from an existing resource.
	 *
	 * The stream MUST be readable and may be writable.
	 *
	 * @param resource $resource The PHP resource to use as the basis for the stream.
	 * @return \Psr\Http\Message\StreamInterface
	 */
	public function createStreamFromResource($resource): StreamInterface
	{
		return new Stream($resource);
	}

	/**
	 * Create a new stream from a string.
	 *
	 * @param string $content String content with which to populate the stream.
	 * @return \Jeht\Support\Streams\StringStream
	 */
	public function createStringStream(string $content = ''): StringStream
	{
		return new StringStream($content);
	}
}

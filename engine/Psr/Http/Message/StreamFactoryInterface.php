<?php
namespace Psr\Http\Message;

use Psr\Http\Message\StreamInterface;

interface StreamFactoryInterface
{
	/**
	 * Create a new stream from a string.
	 *
	 * The stream SHOULD be created with a temporary resource.
	 *
	 * @param string $content String content with which to populate the stream.
	 */
	public function createStream(string $content = ''): StreamInterface;

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
	 *
	 * @throws \RuntimeException If the file cannot be opened.
	 * @throws \InvalidArgumentException If the mode is invalid.
	 */
	public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface;

	/**
	 * Create a new stream from an existing resource.
	 *
	 * The stream MUST be readable and may be writable.
	 *
	 * @param resource $resource The PHP resource to use as the basis for the stream.
	 */
	public function createStreamFromResource($resource): StreamInterface;
}




<?php
namespace Jeht\Support\Streams;

use Throwable;
use LogicException;
use RuntimeException;
use Psr\Http\Message\StreamInterface;

/**
 * Represents a null stream.
 * Implements PSR-7 StreamInterface.
 *
 */
class NullStream extends Stream
{
	/**
	 * This constructor accepts an associative array of custom metadata.
	 *
	 * @param array		$metadata	Associative array of custom metadata.
	 */
	public function __construct(array $metadata = [])
	{
		$this->customMetadata = $metadata;
	}

	/**
	 * Removes the resource from memory and free resource
	 *
	 * @return void
	 */
	public function __destruct()
	{
		$this->close();
	}

	/**
	 * Returns an empty string.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return '';
	}

	/**
	 * Closes the stream and any underlying resources.
	 *
	 * @return void
	 */
	public function close()
	{
		// nothing to do
	}

	/**
	 * Separates any underlying resources from the stream.
	 *
	 * After the stream has been detached, the stream is in an unusable state.
	 *
	 * @return resource|null Underlying PHP stream, if any
	 */
	public function detach()
	{
		// nothing to do
	}

	/**
	 * Assigns a resource to the stream.
	 *
	 * @see __construct
	 *
	 * @param resource	$stream		Stream resource to assign.
	 * @param int|null	$size		Declared size of the stream, in bytes. 
	 * @throws \LogicException upon call 
	 */
	protected function attach($stream, int $size = null)
	{
		throw new \LogicException('Cannot attach to a NullStream instance !');
	}

	/**
	 * Returns zero.
	 *
	 * @return int.
	 */
	public function getSize()
	{
		return 0;
	}

	/**
	 * Returns false.
	 *
	 * @return bool
	 */
	public function tell()
	{
		return false;
	}

	/**
	 * Returns true as a NullStream is always EOF by nature.
	 *
	 * @return bool
	 */
	public function eof()
	{
		return true;
	}

	/**
	 * Returns whether or not the stream is seekable.
	 *
	 * @return bool
	 */
	public function isSeekable()
	{
		return false;
	}

	/**
	 * Seek to a position in the stream.
	 *
	 * @see http://www.php.net/manual/en/function.fseek.php
	 * @param int $offset Stream offset
	 * @param int $whence Specifies how the cursor position will be calculated
	 *	 based on the seek offset. Valid values are identical to the built-in
	 *	 PHP $whence values for `fseek()`.  SEEK_SET: Set position equal to
	 *	 offset bytes SEEK_CUR: Set position to current location plus offset
	 *	 SEEK_END: Set position to end-of-stream plus offset.
	 * @throws \LogicException upon call.
	 */
	public function seek($offset, $whence = SEEK_SET)
	{
		throw new \LogicException('Cannot set position in a NullStream instance !');
	}

	/**
	 * Seek to the beginning of the stream.
	 *
	 * If the stream is not seekable, this method will raise an exception;
	 * otherwise, it will perform a seek(0).
	 *
	 * @throws RuntimeException upon call as NullStream is not seekable.
	 */
	public function rewind()
	{
		throw new RuntimeException('Stream is not seekable.');
	}

	/**
	 * Returns whether or not the stream is writable.
	 *
	 * @return bool
	 */
	public function isWritable()
	{
		return false;
	}

	/**
	 * Write data to the stream.
	 *
	 * @param string $string The string that is to be written.
	 * @return never
	 * @throws \RuntimeException upon call.
	 */
	public function write($string)
	{
		throw new RuntimeException('Stream is not writable.');
	}

	/**
	 * Try to write data to the stream.
	 * Returns the number of bytes written to the stream, false otherwise
	 *
	 * @param string $string The string that is to be written.
	 * @return int|false
	 */
	public function tryWrite($string)
	{
		return false;
	}

	/**
	 * Returns whether or not the stream is readable.
	 *
	 * @return bool
	 */
	public function isReadable()
	{
		return true;
	}

	/**
	 * Read data from the stream.
	 *
	 * @param int $length Read up to $length bytes from the object and return
	 *	 them. Fewer than $length bytes may be returned if underlying stream
	 *	 call returns fewer bytes.
	 * @return string Returns the data read from the stream, or an empty string
	 *	 if no bytes are available.
	 * @throws \RuntimeException if an error occurs.
	 */
	public function read($length)
	{
		if (!$this->readable) {
			throw new RuntimeException('Stream is not readable.');
		}
		//
		return $this->tryRead($length);
	}

	/**
	 * Try to read data from the stream.
	 *
	 * @param int $length Read up to $length bytes from the object and return
	 *	 them. Fewer than $length bytes may be returned if underlying stream
	 *	 call returns fewer bytes.
	 * @return string|false Returns the data read from the stream, or an empty string
	 *	 if no bytes are available, or false on fail.
	 */
	public function tryRead($length)
	{
		return '';
	}

	/**
	 * Returns the remaining contents in a string
	 *
	 * @return string
	 * @throws \RuntimeException if unable to read.
	 * @throws \RuntimeException if error occurs while reading.
	 */
	public function getContents()
	{
		return '';
	}

}

<?php
namespace Jeht\Support\Streams;

use Throwable;
use RuntimeException;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;

/**
 * Handles a PHP stream.
 * Implements PSR-7 StreamInterface.
 *
 * With parts taken (at date 2023-02-04) from the links below
 * @link https://github.com/guzzle/psr7/blob/master/src/Stream.php
 * @link https://github.com/guzzle/streams/blob/master/src/Stream.php
 */
class Stream implements StreamInterface
{
	/**
	 * @see http://php.net/manual/function.fopen.php
	 * @see http://php.net/manual/en/function.gzopen.php
	 */
	private const READABLE_MODES = [
		'r' => true, 'w+' => true, 'r+' => true, 'x+' => true, 'c+' => true,
		'rb' => true, 'w+b' => true, 'r+b' => true, 'x+b' => true,
		'c+b' => true, 'rt' => true, 'w+t' => true, 'r+t' => true,
		'x+t' => true, 'c+t' => true, 'a+' => true,
	];

	/**
	 * @see http://php.net/manual/function.fopen.php
	 * @see http://php.net/manual/en/function.gzopen.php
	 */
	private const WRITABLE_MODES = [
		'w' => true, 'w+' => true, 'rw' => true, 'r+' => true, 'x+' => true,
		'c+' => true, 'wb' => true, 'w+b' => true, 'r+b' => true,
		'x+b' => true, 'c+b' => true, 'w+t' => true, 'r+t' => true,
		'x+t' => true, 'c+t' => true, 'a' => true, 'a+' => true,
	];

	/**
	 * @var resource
	 */
	private $handle;

	/**
	 * @var int|null
	 */
	private $size;

	/**
	 * @var bool
	 */
	private $seekable;

	/**
	 * @var bool
	 */
	private $readable;

	/**
	 * @var bool
	 */
	private $writable;

	/**
	 * @var string|null
	 */
	private $uri;

	/**
	 * @var mixed[]
	 */
	private $customMetadata;

	/**
	 * This constructor accepts an associative array of custom metadata.
	 *
	 * @param resource	$stream		Stream resource to wrap.
	 * @param int|null	$size		Declared size of the stream, in bytes. 
	 * @param array		$metadata	Associative array of custom metadata.
	 *
	 * @throws \InvalidArgumentException if the stream is not a stream resource
	 */
	public function __construct($stream, int $size = null, array $metadata = [])
	{
		$this->attach($stream, $size);
		//
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
	 * Reads all data from the stream into a string, from the beginning to end.
	 *
	 * This method MUST attempt to seek to the beginning of the stream before
	 * reading data and read the stream until the end is reached.
	 *
	 * Warning: This could attempt to load a large amount of data into memory.
	 *
	 * This method MUST NOT raise an exception in order to conform with PHP's
	 * string casting operations.
	 *
	 * @see http://php.net/manual/en/language.oop5.magic.php#object.tostring
	 * @return string
	 */
	public function __toString()
	{
		try {
			if ($this->isSeekable()) {
				// save current position
				$current = $this->tell();
				// go to start
				$this->seek(0);
				// gathers
				$contents = $this->getContents();
				// restore current position
				$this->seek($current);
				// that's it
				return $contents;
			}
			//
			return $this->getContents();
		}
		catch (Throwable $t) {
			if (\PHP_VERSION_ID >= 70400) {
				throw $t;
			}
			//
			$message = self::class . '::__toString exception: ' . $t;
			//
			trigger_error($message, E_USER_ERROR);
			//
			return '';
		}
	}

	/**
	 * Fix clone of streamed data.
	 *
	 * @return void
	 */
	public function __clone()
	{
		$handle = fopen('php://memory', 'w+');
		//
		if ($this->size > 0) {
			stream_copy_to_stream($this->handle, $handle);
		}
		//
		$this->attach($handle);
	}

	/**
	 * Closes the stream and any underlying resources.
	 *
	 * @return void
	 */
	public function close()
	{
		if (is_resource($this->handle)) {
			fclose($this->handle);
		}
		//
		$this->detach();
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
		$result = $this->handle;
		//
		$this->handle = null;
		$this->size = $this->uri = null;
		$this->readable = $this->writable = $this->seekable = false;
		//
		return $result;
	}

	/**
	 * Assigns a resource to the stream.
	 *
	 * @see __construct
	 *
	 * @param resource	$stream		Stream resource to assign.
	 * @param int|null	$size		Declared size of the stream, in bytes. 
	 *
	 * @throws \InvalidArgumentException if the stream is not a stream resource
	 */
	protected function attach($stream, int $size = null)
	{
		if (!is_resource($stream)) {
			throw new \InvalidArgumentException('Stream must be a resource');
		}
		//
		$this->handle = $stream;
		//
		$meta = stream_get_meta_data($stream);
		//
		if (!is_null($size)) {
			$this->size = $size;
		} else {
			$fstat = fstat($stream);
			$this->size = $fstat['size'] ?? null;
		}
		//
		$this->seekable = $meta['seekable'];
		$this->readable = isset(self::READABLE_MODES[$meta['mode']]);
		$this->writable = isset(self::WRITABLE_MODES[$meta['mode']]);
		//
		$this->uri = $this->getMetadata('uri');
	}

	/**
	 * Get the size of the stream if known.
	 *
	 * @return int|null Returns the size in bytes if known, or null if unknown.
	 */
	public function getSize()
	{
		if (!is_null($this->size)) {
			return $this->size;
		}
		//
		if (!$this->handle) {
			return null;
		}
		//
		// Clear stat cache if the stream has a uri
		if ($this->uri) {
			clearstatcache(true, $this->uri);
		}
		//
		$stats = fstat($this->handle);
		if (isset($stats['size'])) {
			return $this->size = $stats['size'];
		}
		//
		return null;
	}

	/**
	 * Returns the current position of the file read/write pointer
	 *
	 * @return int Position of the file pointer
	 * @throws \RuntimeException on error.
	 */
	public function tell()
	{
		return $this->handle ? ftell($this->handle) : false;
	}

	/**
	 * Returns true if the stream is at the end of the stream.
	 *
	 * @return bool
	 */
	public function eof()
	{
		return !$this->handle || feof($this->handle);
	}

	/**
	 * Returns whether or not the stream is seekable.
	 *
	 * @return bool
	 */
	public function isSeekable()
	{
		return $this->seekable;
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
	 * @throws \RuntimeException on failure.
	 */
	public function seek($offset, $whence = SEEK_SET)
	{
		return $this->seekable
			? 0 === fseek($this->handle, $offset, $whence)
			: false;
	}

	/**
	 * Seek to the beginning of the stream.
	 *
	 * If the stream is not seekable, this method will raise an exception;
	 * otherwise, it will perform a seek(0).
	 *
	 * @see seek()
	 * @see http://www.php.net/manual/en/function.fseek.php
	 * @throws \RuntimeException on failure.
	 */
	public function rewind()
	{
		if (!$this->seekable) {
			throw new RuntimeException('Stream is not seekable.');
		}
		//
		$this->seek(0);
		//
		return $this;
	}

	/**
	 * Returns whether or not the stream is writable.
	 *
	 * @return bool
	 */
	public function isWritable()
	{
		return $this->writable;
	}

	/**
	 * Write data to the stream.
	 *
	 * @param string $string The string that is to be written.
	 * @return int Returns the number of bytes written to the stream.
	 * @throws \RuntimeException on failure.
	 */
	public function write($string)
	{
		if (!$this->writable) {
			throw new RuntimeException('Stream is not writable.');
		}
		//
		return $this->tryWrite($string);
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
		return $this->writable ? fwrite($this->handle, $string) : false;
	}

	/**
	 * Returns whether or not the stream is readable.
	 *
	 * @return bool
	 */
	public function isReadable()
	{
		return $this->readable;
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
		return $this->readable ? fread($this->handle, $length) : false;
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
		if (!isset($this->handle)) {
			throw new RuntimeException('Stream is detached.');
		}
		//
		if (!isset($this->readable)) {
			throw new RuntimeException('Cannot read from non-readable stream.');
		}
		//
		return StreamUtils::tryGetContents($this->handle);
	}

	/**
	 * Get stream metadata as an associative array or retrieve a specific key.
	 *
	 * The keys returned are identical to the keys returned from PHP's
	 * stream_get_meta_data() function.
	 *
	 * @see http://php.net/manual/en/function.stream-get-meta-data.php
	 * @param string $key Specific metadata to retrieve.
	 * @return array|mixed|null Returns an associative array if no key is
	 *	 provided. Returns a specific key value if a key is provided and the
	 *	 value is found, or null if the key is not found.
	 */
	public function getMetadata($key = null)
	{
		if (!$this->handle) {
			return $key ? null : [];
		}
		//
		if (!$key) {
			return $this->customMetadata + stream_get_meta_data($this->handle);
		}
		//
		if (isset($this->customMetadata[$key])) {
			return $this->customMetadata[$key];
		}
		//
		$meta = stream_get_meta_data($this->handle);
		//
		return $meta[$key] ?? null;
	}

	/**
	 * Returns a StringStream instance with the same data and metadata.
	 *
	 * @return \Jeht\Support\Streams\StringStream
	 */
	public function toStringStream()
	{
		return new StringStream(
			$this->__toString(), $this->getMetadata()
		);
	}

	/**
	 * Validates $mode parameter for use with fopen().
	 *
	 * @param string $mode
	 * @return bool
	 */
	public static function isValidMode(string $mode)
	{
		return array_key_exists($mode, self::READABLE_MODES)
			|| array_key_exists($mode, self::WRITABLE_MODES);
	}

	/**
	 * Checks if $mode parameter is a readable mode for fopen().
	 *
	 * @param string $mode
	 * @return bool
	 */
	public static function isReadableMode(string $mode)
	{
		return array_key_exists($mode, self::READABLE_MODES);
	}

	/**
	 * Checks if $mode parameter is a writable mode for fopen().
	 *
	 * @param string $mode
	 * @return bool
	 */
	public static function isWritableMode(string $mode)
	{
		return array_key_exists($mode, self::WRITABLE_MODES);
	}

	/**
	 * Checks if the given $resource is readable.
	 *
	 * @param resource $handle
	 * @return bool
	 * @throws \InvalidArgumentException
	 */
	public static function resourceIsReadable($handle)
	{
		if (!is_resource($handle)) {
			throw new InvalidArgumentException('The passed argument is not a resource.');
		}
		//
		$metadata = stream_get_meta_data($handle);
		$mode = $metadata['mode'] ?? '';
		//
		return self::isReadableMode($mode);
	}

	/**
	 * Checks if the given $resource is writable.
	 *
	 * @param resource $handle
	 * @return bool
	 * @throws \InvalidArgumentException
	 */
	public static function resourceIsWritable($handle)
	{
		if (!is_resource($handle)) {
			throw new InvalidArgumentException('The passed argument is not a resource.');
		}
		//
		$metadata = stream_get_meta_data($handle);
		$mode = $metadata['mode'] ?? '';
		//
		return self::isWritableMode($mode);
	}

}

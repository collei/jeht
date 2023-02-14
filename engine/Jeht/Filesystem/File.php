<?php
namespace Jeht\Filesystem;

use Jeht\Interfaces\Filesystem\File as FileInterface;

/** 
 * A file
 *
 * @author Collei de Laravel <alarido.su@gmail.com>
 */
class File implements FileInterface
{
	/**
	 * @var string
	 */
	protected $path;

	/**
	 * @var array
	 */
	protected $pathInfo;

	/**
	 * @var $folderName
	 */
	protected $folderName;

	/**
	 * @var bool
	 */
	protected $remote;

	/**
	 * @var bool
	 */
	protected $moved;

	/**
	 * Creates and returns a File instance
	 *
	 * @param string $path	Must be the full path of the file.
	 * @return static
	 */
	public static function for(string $path)
	{

die(__FILE__.', '.__LINE__.': '.print_r('ok',true));

		return new static($path);
	}

	/**
	 * Builds a File instance
	 *
	 * @param string $path	Must be the full path of the file.
	 */
	public function __construct(string $path)
	{
		// let's cache 'em all !
		$this->pathInfo = pathinfo(
			$this->path = $path
		);
		//
		// name of the folder where the file lies in
		$this->folderName = basename(dirname($path));
		//
		// true if the path is any kind of remote uri
		$this->remote = preg_match('#^(\w+:)?(\\/\\/|\\\\\\\\)#', $path);
		//
		// should be not remote if starting with file:///
		if ($this->remote) {
			if (preg_match('#^file:\/\/\/#', $path)) {
				$this->remote = false;
			}
		}
		//
		// file was not yet moved.
		$this->moved = false;
	}

	/**
	 * Alias of getBaseName()
	 * @see getBaseName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->getBaseName();
	}

	/**
	 * Returns the name of the file.
	 *
	 * e. g., for "/home/xyz/myfile.txt", it should return "myfile.txt" as string,
	 * without change case or something else.
	 *
	 * @return string
	 */
	public function getBaseName()
	{
		return $this->pathInfo['basename'];
	}

	/**
	 * Returns the extension of the file.
	 *
	 * e. g., for "/home/xyz/myfile.txt", it should return "txt" as string,
	 * without the dot, and without change case or something else.
	 *
	 * @return string
	 */
	public function getType()
	{
		return $this->pathInfo['extension'];
	}

	/**
	 * Returns the filename of the file (the part without extension).
	 *
	 * e. g., for "/home/xyz/myfile.txt", it should return "myfile"
	 * as string, without the leading dot, and without change case or
	 * something else.
	 *
	 * @return string
	 */
	public function getFileName()
	{
		return $this->pathInfo['filename'];
	}
	
	/**
	 * Returns the path to the folder where the file is.
	 *
	 * e. g., for "/home/xyz/myfile.txt", it should return "/home/xyz"
	 * as string, without the trailing slash, and without change case or
	 * something else.
	 *
	 * @return string
	 */
	public function getFolderPath()
	{
		return $this->pathInfo['dirname'];
	}
	
	/**
	 * Returns the folder name where the file is.
	 *
	 * e. g., for "/home/xyz/myfile.txt", it should return "xyz"
	 * as string, without change case or something else.
	 * If the file is at root, it should return an empty string.
	 *
	 * @return string
	 */
	public function getFolderName()
	{
		return $this->folderName;
	}
	
	/**
	 * Returns the path to the folder where the file is.
	 *
	 * e.g., for "/home/xyz/myfile.txt", it should return "/home/xyz/myfile.txt"
	 * as string, without the trailing slash, and without change case or
	 * something else.
	 *
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}
	
	/**
	 * Returns the size of the file, or false if it does not exist.
	 *
	 * @return int|false
	 */
	public function getSize()
	{
		return $this->exists() ? @filesize($this->path) : false;
	}
	
	/**
	 * Alias of getPath().
	 * @see getPath()
	 *
	 * @return string
	 */
	public function path()
	{
		return $this->getPath();
	}

	/**
	 * Returns if the file exists or not.
	 *
	 * @return bool
	 */
	public function exists()
	{
		return file_exists($this->path);
	}
	
	/**
	 * Returns if the file exists and is readable.
	 *
	 * @return bool
	 */
	public function isReadable()
	{
		return is_readable($this->path);
	}
	
	/**
	 * Returns if the file is a URL.
	 *
	 * @return bool
	 */
	public function isRemote()
	{
		return $this->remote;
	}

	/**
	 * Returns if the file was moved.
	 *
	 * @return bool
	 */
	public function wasMoved()
	{
		return $this->moved;
	}

	/**
	 * Copies the file to the specified $destination.
	 * Returns a corresponding instance of \Jeht\Interfaces\Filesystem\File.
	 * 
	 * It should throw an exception when:
	 * - the source file does not exist
	 * - the destination folder does not exist
	 * - is there any file at $destination with same name
	 *
	 * @param string|\Jeht\Interfaces\Filesystem\Folder
	 * @return \Jeht\Interfaces\Filesystem\File|null
	 * @throws \RuntimeException if the source file or the $destination folder
	 *   does not exist, or if yet there is a $destination file
	 * @throws \InvalidArgumentException if $destination is not either
	 *   a string or an instance of \Jeht\Interfaces\Filesystem\Folder.
	 */
	public function copyTo($destination)
	{
		$source = $this->path;
		//
		if ($destination instanceof FileInterface) {
			$destination = $destination->getPath();
		}
		//
		if (!is_string($destination)) {
			throw new InvalidArgumentException(
				"Parameter must be a string or instanceof " . FileInterface::class . "."
			);
		}
		//
		if ('/' === substr($destination, -1)) {
			$destination = $destination . $this->getFileName();
		}
		//
		$destinationFolder = dirname($destination);
		//
		if (! $this->exists()) {
			throw new RuntimeException(
				"Source file [$source] does not exist."
			);
		}
		//
		if (!is_dir($destinationFolder)) {
			throw new RuntimeException(
				"Destination folder [$destinationFolder] does not exist."
			);
		}
		//
		if (is_file($destination)) {
			throw new RuntimeException(
				"Cannot overwrite the existing file [$destination]."
			);
		}
		//
		if (copy($source, $destination)) {
			return new self($destination);
		}
		//
		return null;
	}

	/**
	 * Moves the file to the specified $destination.
	 * Returns a corresponding instance of \Jeht\Interfaces\Filesystem\File.
	 *
	 * It should throw an exception when:
	 * - the source file does not exist
	 * - the destination folder does not exist
	 * - is there any file at $destination with same name
	 * - the source file is a remote file
	 *
	 * @param string|\Jeht\Interfaces\Filesystem\Folder
	 * @return bool
	 * @throws \RuntimeException if source file or $destination folder does not exist
	 * @throws \RuntimeException if a $destination file already exists
	 * @throws \RuntimeException when moveTo() gets called by the 2nd, 3rd, Nth time
	 * @throws \InvalidArgumentException if $destination is not either
	 *   a string or an instance of \Jeht\Interfaces\Filesystem\Folder.
	 * @throws \Jeht\Exceptions\InvalidOperationException
	 *   if the source file is a remote file
	 */
	public function moveTo($destination)
	{
		if ($this->moved) {
			throw new InvalidOperationException("The file was already moved.");
		}
		//
		$source = $this->path;
		//
		if ($this->isRemote()) {
			throw new InvalidOperationException(
				"Source file [$source] is remote and cannot be deleted."
			);
		}
		//
		if ($destination instanceof FileInterface) {
			$destination = $destination->getPath();
		}
		//
		if (!is_string($destination)) {
			throw new InvalidArgumentException(
				"Parameter must be a string or instanceof " . FileInterface::class . "."
			);
		}
		//
		if ('/' === substr($destination, -1)) {
			$destination = $destination . $this->getFileName();
		}
		//
		$destinationFolder = dirname($destination);
		//
		if (! $this->exists()) {
			throw new RuntimeException(
				"Source file [$source] does not exist."
			);
		}
		//
		if (!is_dir($destinationFolder)) {
			throw new RuntimeException(
				"Destination folder [$destinationFolder] does not exist."
			);
		}
		//
		if (is_file($destination)) {
			throw new RuntimeException(
				"Cannot overwrite the existing file [$destination]."
			);
		}
		//
		if (copy($source, $destination)) {
			$this->moved = true;
			//
			return new self($destination);
		}
		//
		return null;
	}

	/**
	 * Opens and returns a \Jeht\Interfaces\Filesystem\FileStream instance.
	 *
	 * It should throw an exception when:
	 * - the source file does not exist
	 * - the source file is unable to be opened for reading
	 *
	 * @param string|null	stream mode as used by fopen(). Defaults to 'r'.
	 * @return \Jeht\Interfaces\Filesystem\FileStream
	 * @throws \RuntimeException if the source file does not exist or
	 *   it is unable to be opened for reading.
	 * @throws \InvalidArgumentException if $mode is invalid or not recognized.
	 */
	public function openAsStream(string $mode = null)
	{
		if (! $this->exists()) {
			throw new RuntimeException("The file [$source] does not exist.");
		}
		//
		if (! $this->isReadable()) {
			throw new RuntimeException("The file [$source] cannot be read.");
		}
		//
		$mode = $mode ?? 'r';
		//
		// Validates $mode argument for use with fopen()
		if (! FileStream::isValidMode($mode)) {
			throw new InvalidArgumentException("Mode [$mode] is invalid.");
		}
		//
		try {
			$handle = @fopen($this->path, $mode);
			//
			$fileStream = return new FileStream($handle);
			//
		} catch (Throwable $t) {
			$message = 'from [' . get_class($t) . ']: ' . $t->getMessage() . '.';
			//
			throw new RuntimeException($message);
		}
		//
		return $fileStream;
	}

}


<?php
namespace Jeht\Interfaces\Filesystem;

/** 
 * Defines a File interface
 *
 * @author Collei de Laravel <alarido.su@gmail.com>
 */
interface File extends FilesystemItem
{
	/**
	 * Returns the size of the file, or false if it does not exist.
	 *
	 * @return int|false
	 */
	public function getSize();

	/**
	 * Copies the file to the specified $destination.
	 *
	 * It should throw an exception when:
	 * - the source file does not exist
	 * - the destination folder does not exist
	 * - is there any file at $destination with same name
	 *
	 * @param string|\Jeht\Interfaces\Filesystem\Folder
	 * @return bool
	 * @throws \RuntimeException if the source file or the $destination folder
	 *   does not exist
	 * @throws \InvalidArgumentException if $destination is not either
	 *   a string or an instance of \Jeht\Interfaces\Filesystem\Folder.
	 */
	public function copyTo(string $destination);

	/**
	 * Moves the file to the specified $destination.
	 *
	 * It should throw an exception when:
	 * - the source file does not exist
	 * - the destination folder does not exist
	 * - is there any file at $destination with same name
	 * - the source file is a remote file
	 *
	 * @param string|\Jeht\Interfaces\Filesystem\Folder
	 * @return bool
	 * @throws \RuntimeException if the source file or the $destination folder
	 *   does not exist
	 * @throws \InvalidArgumentException if $destination is not either
	 *   a string or an instance of \Jeht\Interfaces\Filesystem\Folder.
	 * @throws \Jeht\Interfaces\Filesystem\InvalidOperationException
	 *   if the source file is a remote file
	 */
	public function moveTo(string $destination);

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
	public function openAsStream(string $mode = null);
}

<?php
namespace Jeht\Interfaces\Filesystem;

/** 
 * Defines a File interface
 *
 * @author Collei de Laravel <alarido.su@gmail.com>
 */
interface File
{
	/**
	 * Returns the name of the file.
	 *
	 * e. g., for "/home/xyz/myfile.txt", it should return "myfile" as string,
	 * without change case or something else.
	 *
	 * @return string
	 */
	public function getName();

	/**
	 * Returns the extension of the file.
	 *
	 * e. g., for "/home/xyz/myfile.txt", it should return "txt" as string,
	 * without the dot, and without change case or something else.
	 *
	 * @return string
	 */
	public function getType();

	/**
	 * Returns the filename of the file.
	 *
	 * e. g., for "/home/xyz/myfile.txt", it should return "myfile.txt"
	 * as string, without the leading dot, and without change case or
	 * something else.
	 *
	 * @return string
	 */
	public function getFileName();
	
	/**
	 * Returns the path to the folder where the file is.
	 *
	 * e. g., for "/home/xyz/myfile.txt", it should return "/home/xyz"
	 * as string, without the trailing slash, and without change case or
	 * something else.
	 *
	 * @return string
	 */
	public function getFolderPath();
	
	/**
	 * Returns the folder name where the file is.
	 *
	 * e. g., for "/home/xyz/myfile.txt", it should return "xyz"
	 * as string, without change case or something else.
	 * If the file is at root, it should return an empty string.
	 *
	 * @return string
	 */
	public function getFolderName();
	
	/**
	 * Returns the path to the folder where the file is.
	 *
	 * e.g., for "/home/xyz/myfile.txt", it should return "/home/xyz/myfile.txt"
	 * as string, without the trailing slash, and without change case or
	 * something else.
	 *
	 * @return string
	 */
	public function getPath();
	
	/**
	 * Returns the size of the file, or false if it does not exist.
	 *
	 * @return int|false
	 */
	public function getSize();
	
	/**
	 * Returns if the file exists or not.
	 *
	 * @return bool
	 */
	public function exists();
	
	/**
	 * Returns if the file exists and is readable.
	 *
	 * @return bool
	 */
	public function isReadable();
	
	/**
	 * Returns if the file is a URL.
	 *
	 * @return bool
	 */
	public function isRemote();
	
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

<?php
namespace Jeht\Interfaces\Filesystem;

/** 
 * A file
 *
 * @author Collei de Laravel <alarido.su@gmail.com>
 */
interface FilesystemItem
{
	/**
	 * Returns the filename of the file.
	 *
	 * e. g., for "/home/xyz/myfile.txt", it should return "myfile.txt"
	 * as string, without the leading dot, and without change case or
	 * something else.
	 *
	 * @return string
	 */
	public function getName();
	
	/**
	 * Returns the name of the file.
	 *
	 * e. g., for "/home/xyz/myfile.txt", it should return "myfile" as string,
	 * without change case or something else.
	 *
	 * @return string
	 */
	public function getBaseName();

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
}


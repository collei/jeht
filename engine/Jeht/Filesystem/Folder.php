<?php
namespace Jeht\Filesystem;

use Jeht\Interfaces\Filesystem\Folder as FolderInterface;
use Jeht\Support\Str;

class Folder implements FolderInterface
{
	/**
	 * @var int TYPE_ALL
	 * @var int TYPE_FILE
	 * @var int TYPE_FOLDER
	 */
	public const TYPE_ALL = 3;
	public const TYPE_FILE = 1;
	public const TYPE_FOLDER = 2;

	/**
	 * @var string
	 */
	protected $path;

	/**
	 * @var array
	 */
	protected $pathInfo;

	/**
	 * @var string
	 */
	protected $folderName;

	/**
	 * @var bool
	 */
	protected $remote;

	/**
	 * @var bool
	 */
	protected $exists;

	/**
	 * @var array
	 */
	protected $items;

	/**
	 * @var bool
	 */
	protected $itemsAsNative = false;

	/**
	 * Creates and returns a Folder instance for the given $path.
	 *
	 * @param string $path
	 * @return static
	 */
	public static function for(string $path)
	{
		return new static($path);
	}

	/**
	 * Builds a new Folder instance for the given $path.
	 *
	 * @param string $path
	 */
	public function __construct(string $path)
	{
		// let's cache 'em all !
		$this->pathInfo = pathinfo(
			$this->path = $path
		);
		//
		// name of the parent folder
		$this->folderName = basename(dirname($path));
		//
		// Folder is able to only work locally
		$this->remote = false;
		//
		$this->exists = is_dir($path);
		$this->items = [];
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
	 * Returns the name of the folder.
	 *
	 * e. g., for "/home/xyz/myfolder", it should return "myfolder" as string,
	 * without change case or something else.
	 *
	 * @return string
	 */
	public function getBaseName()
	{
		return $this->pathInfo['basename'];
	}

	/**
	 * Returns the extension of the folder.
	 *
	 * e. g., for "/home/xyz/myfolder", it should return "" as string,
	 * without the dot, and without change case or something else.
	 *
	 * @return string
	 */
	public function getType()
	{
		return $this->pathInfo['extension'];
	}

	/**
	 * Returns the path to the parent folder.
	 *
	 * e. g., for "/home/xyz/myfolder", it should return "/home/xyz"
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
	 * Returns the parent folder name.
	 *
	 * e. g., for "/home/xyz/myfolder", it should return "xyz"
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
	 * Returns the full path to the folder.
	 *
	 * e.g., for "/home/xyz/myfolder", it should return "/home/xyz/myfolder"
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
	 * Returns if the folder exists or not.
	 *
	 * @return bool
	 */
	public function exists()
	{
		return $this->exists;
	}
	
	/**
	 * Returns if the folder exists and is readable.
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
	 * Searches for files and subfolders. Skips '.' and '..'.
	 *
	 * @param bool $forceRefresh = false
	 * @return static
	 */
	public function all($forceRefresh = false)
	{
		return $this->cachedScanFor(self::TYPE_ALL, $forceRefresh);
	}

	/**
	 * Searches for files. Skips '.' and '..'.
	 *
	 * @param bool $forceRefresh = false
	 * @return static
	 */
	public function files($forceRefresh = false)
	{
		return $this->cachedScanFor(self::TYPE_FILE, $forceRefresh);
	}

	/**
	 * Searches for subfolders. Skips '.' and '..'.
	 *
	 * @param bool $forceRefresh = false
	 * @return static
	 */
	public function subfolders($forceRefresh = false)
	{
		return $this->cachedScanFor(self::TYPE_FOLDER, $forceRefresh);
	}

	/**
	 * Instructs to return files as instances of \SplFileInfo. 
	 *
	 * @return $this
	 */
	public function asNative()
	{
		$this->itemsAsNative = true;
		//
		return $this;
	}

	/**
	 * Instructs to return files as instances of \Jeht\Filesystem\File. 
	 *
	 * @return $this
	 */
	public function asJeht()
	{
		$this->itemsAsNative = false;
		//
		return $this;
	}

	/**
	 * Does the search in the filesystem if needed or when instructed to do so,
	 * skipping '.' and '..', and internally caches the result.
	 * Otherwise, does nothing.
	 *
	 * @param int $target
	 * @param bool $forceRefresh = false
	 * @return static
	 */
	protected function cachedScanFor(int $target, bool $forceRefresh = false)
	{
		if (empty($this->items) || $forceRefresh) {
			$this->items = $this->scanFor($target);
		}
		//
		return $this;
	}

	/**
	 * Searches for the files/subfolders in the current folder, skipping '.'
	 * and '..', then returns them as an array of instances.
	 *
	 * @param int $target
	 * @return array
	 */
	protected function scanFor(int $target)
	{
		$items = scandir($this->path);
		$result = [];
		//
		foreach ($items as $key => $name) {
			if ('.' === $name || '..' === $name) {
				continue;
			}
			//
			$fullPath = $this->path.DIRECTORY_SEPARATOR.$name;
			//
			if (self::TYPE_FILE === $target && is_file($fullPath)) {
				$result[$name] = $this->itemsAsNative
					? new SplFileInfo($fullPath)
					: File::for($fullPath);
			} elseif (self::TYPE_FOLDER === $target && is_dir($fullPath)) {
				$result[$name] = static::for($fullPath);
			} else {
				$result[$name] = is_dir($fullPath)
					? static::for($fullPath)
					: ($this->itemsAsNative
							? new SplFileInfo($fullPath)
							: File::for($fullPath)
					);
			}
		}
		//
		return $result;
	}

	/**
	 * Create the folder in the filesystem if it does not exist yet.
	 *
	 * @return static
	 */
	public function create()
	{
		if (! $this->exists) {
			mkdir($this->path, 0777, true);
			//
			$this->exists = true;
		}
		//
		return $this;
	}

	/**
	 * Returns the folder path.
	 *
	 * @return string
	 */
	public function path()
	{
		return $this->path;
	}

	/**
	 * Returns the items.
	 *
	 * @return array
	 */
	public function get()
	{
		return $this->items;
	} 

	/**
	 * Returns the items as a list of path.
	 *
	 * @return array
	 */
	public function asPathList()
	{
		return array_map(function($item){
			return $item->path();
		}, $this->items);
	}

	/**
	 * Returns the items whose names matches the given wildcard pattern.
	 *
	 * @return array
	 */
	public function withName(string $name)
	{
		$pattern = Str::wildcardToRegex($name, '#');
		//
		return array_filter($this->items, function($item) use ($pattern){
			return 1 === preg_match($pattern, $item->getName());
		});
	}

}



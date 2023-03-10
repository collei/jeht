<?php
namespace Jeht\Filesystem;

class FolderTreeCreator
{
	/**
	 * @var string
	 */
	protected $basePath;

	/**
	 * @var array
	 */
	protected $folders;

	/**
	 * @var array
	 */
	protected $createdFolders;

	/**
	 * Create a folder in the tree if it does not exist yet.
	 *
	 * @param string $folder
	 * @return void
	 */
	protected function createFolder(string $folder)
	{
		$path = $this->basePath.DIRECTORY_SEPARATOR.$folder;
		//
		clearstatcache();
		// Recreate folder if it does not exist yet
		if (! is_dir($path)) {
			mkdir($path, 0777, true);
		}
	}

	/**
	 * Create every folder in the tree if it does not exist yet.
	 *
	 * @return static
	 */
	protected function createFolders()
	{
		foreach ($this->folders as $folder) {
			$this->createFolder($folder);
		}
		//
		return $this;
	}

	/**
	 * Initializes the folder tree creator
	 *
	 * @param string|null $basePath
	 */
	public function __construct(string $basePath = null)
	{
		$this->folders = [];
		$this->createdFolders = [];
		//
		if (!is_null($basePath)) {
			$this->setBasePath($basePath);
		}
	}

	/**
	 * Defines the proper basepath on which we work upon.
	 *
	 * @param string $basePath
	 * @return static
	 */
	public function setBasePath(string $basePath)
	{
		$this->basePath = str_replace(
			['\\\\','\\','//','/'], DIRECTORY_SEPARATOR, $basePath
		);
		//
		return $this;
	}

	/**
	 * Defines the folder tree we work with.
	 *
	 * @param array $folders
	 * @return static
	 */
	public function setFolders(array $folders)
	{
		$this->folders = $folders;
		//
		return $this;
	}

	/**
	 * Defines the basepath we work within.
	 *
	 * @param string $basePath
	 * @return static
	 */
	public function at(string $basePath)
	{
		return $this->setBasePath($basePath);
	}

	/**
	 * Defines the folder tree we work with.
	 *
	 * @param array $folders
	 * @return static
	 */
	public function with(array $folders)
	{
		return $this->setFolders($folders);
	}

	/**
	 * Does its dishes.
	 *
	 * @return void
	 */
	public function create()
	{
		$this->createFolders($this->folders);
	}

	/**
	 * Initializes a instance upon a given $basePath.
	 *
	 * @return void
	 */
	public static function within(string $basePath)
	{
		return new static($basePath);
	}

	/**
	 * Initializes a instance with a given $folders tree.
	 *
	 * @return void
	 */
	public static function for(array $folders)
	{
		return (new static())->setFolders($folders);
	}

}


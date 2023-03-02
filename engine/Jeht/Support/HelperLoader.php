<?php
namespace Jeht\Support;

use Jeht\Filesystem\Folder;

/**
 * Loads helper files.
 */
class HelperLoader
{
	/**
	 * @var array
	 */
	protected $helpers;

	/**
	 * @var array
	 */
	protected $required;

	/**
	 * @var array
	 */
	protected $failed;

	/**
	 * Creates a new instance of it.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->helpers = [];
		$this->required = [];
		$this->failed = [];
	}

	/**
	 * Adds a helper file path.
	 *
	 * @param string $path
	 * @return $this
	 */
	public function addHelper(string $path)
	{
		$this->helpers[$path] = $path;
		//
		return $this;
	}

	/**
	 * Adds several helper files at once.
	 *
	 * @param array $helpers
	 * @return $this
	 */
	public function addHelpers(array $helpers)
	{
		$this->helpers = array_merge($this->helpers, $helpers);
		//
		return $this;
	}

	/**
	 * Adds helper files from the given folder.
	 *
	 * @param string $folder
	 * @return $this
	 */
	public function addHelpersFrom(string $folder)
	{
		if (! is_dir($folder)) {
			return $this;
		}
		//
		$phpFiles = Folder::for($folder)->files()->withName('*.php')->get();
		//
		foreach ($phpFiles as $phpFile) {
			$this->addHelper($phpFile->getPath());
		}
		//
		return $this;
	}

	/**
	 * Requires all added helper files that is_readable().
	 *
	 * @return $this
	 */
	public function require()
	{
		$helpers = $this->helpers;
		//
		foreach ($helpers as $helper) {
			if (is_readable($helper)) {
				$this->required[$helper] = $helper;
				//
				@require $helper;
			} else {
				$this->failed[$helper] = $helper;
			}
			//
			unset($this->helpers[$helper]);
		}
		//
		return $this;
	}

	/**
	 * Returns all pending (i.e., added but not yet required) helper files.
	 *
	 * @return array
	 */
	public function getHelpers()
	{
		return $this->helpers;
	}

	/**
	 * Returns all successfully required helper files.
	 *
	 * @return array
	 */
	public function getRequiredHelpers()
	{
		return $this->required;
	}

	/**
	 * Returns all failed helper files (not required because not is_readable()).
	 *
	 * @return array
	 */
	public function getFailedHelpers()
	{
		return $this->failed;
	}

}


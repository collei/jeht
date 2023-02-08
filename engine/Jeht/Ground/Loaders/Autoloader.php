<?php
namespace Jeht\Ground\Loaders;

/**
 * Specialized autoloader for the classes in the 'client' namespace.
 * It ignores classes of other namespaces, included the Kernel ones
 * (the Kernel and vendor will be resolved by the Composer autoloader).
 *
 */
class Autoloader
{
	/**
	 * Remembers previously required files
	 *
	 * @var array
	 */
	private $previouslyLoaded = [];

	/**
	 * The application namespace
	 *
	 * @var string
	 */
	private $namespace;

	/**
	 * The application class root
	 *
	 * @var string
	 */
	private $rootPath;

	/**
	 * The autoloader instance
	 *
	 * @var string
	 */
	private static $instance = null;

	/**
	 * Store the file being required.
	 *
	 * @param	string	$class	the fully namespaced class name
	 * @param	string	$file	the physical file path
	 * @return	void
	 */
	protected function addLoaded(string $class, string $file)
	{
		$this->previouslyLoaded[$class] = $file;
	}

	/**
	 * Ask if such class file has been required.
	 *
	 * @param	string	$class	the fully namespaced class name
	 * @return	bool
	 */
	protected function loadedExists(string $class)
	{
		return \array_key_exists($class, $this->previouslyLoaded);
	}

	/**
	 * Register the autoloader class with the PHP
	 *
	 * @return	void
	 */
	protected function autoloadRegister()
	{
		\spl_autoload_register([$this, 'load'], true, true);
	}

	/**
	 * Loads and requires a class.
	 *
	 * @param	string	$class	the fully namespaced class name
	 * @return	void
	 */
	public function load($class)
	{
		// ignore non-'client' classes
		if (substr($class, 0, 4) !== $this->namespace) {
			return;
		}
		//
		// ignore those that had been loaded before
		if ($file = $this->loadedExists($class)) {
			return;
		}
		//
		$file = $this->rootPath . DIRECTORY_SEPARATOR
			. \str_replace('\\', DIRECTORY_SEPARATOR, $class)
			. '.php';
		//
		if (\file_exists($file)) {
			// register the class as required
			$this->addLoaded($class, $file);
			//
			require $file;
		}
	}

	/**
	 * Initializes a new instance.
	 *
	 * @param	string	$namespace	the namespace root, e.g., App\
	 * @param	string	$rootPath	its physical root in the filesystem
	 * @return	void
	 */
	public function __construct(string $namespace, string $rootPath)
	{
		$this->namespace = $namespace;
		$this->rootPath = $rootPath;
		//
		$this->autoloadRegister();
	}

	/**
	 * Initializes an instance and registers the autoloader. 
	 *
	 * @param	string	$namespace	the namespace root, e.g., App\
	 * @param	string	$rootPath	its physical root in the filesystem
	 * @return	self
	 */
	public static function register(string $namespace, string $rootPath)
	{
		return (self::$instance = new self($namespace, $rootPath));
	}
	
}


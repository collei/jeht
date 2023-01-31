<?php
namespace Ground\Kernel\Web;

use Ground\Kernel\Loaders\Autoloader;
use Ground\Container\Container;
use Ground\Http\Routing\Router;
use Ground\Http\Request\HttpRequest;

class Application extends Container
{
	/**
	 * @var @static self
	 */
	private static $instance;

	/**
	 * @var @static string[]
	 */
	private static $folders = [
		'app','config','public','resources','storage','test'
	];

	/**
	 * @var @static string[]
	 */
	private static $autocreateable = [
		'storage' => ['logging','cache']
	];

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var string
	 */
	private $baseDir;

	/**
	 * @var string[]
	 */
	private $configuredFolder = [];

	/**
	 * Register a given folder with the application
	 *
	 * @param string $folder
	 * @param string $path
	 * @return void;
	 */
	protected function configureFolder(string $folder, string $path)
	{
		$this->configuredFolder[$folder] = $path;		
	}

	/**
	 * Configure the basic folders for the application
	 *
	 * @return void;
	 */
	protected function configureFolders()
	{
		foreach (self::$folders as $folder) {
			$pathSpec = $this->baseDir . DIRECTORY_SEPARATOR . $folder;
			//
			// Register folder if it exists
			if (is_dir($pathSpec)) {
				$this->configureFolder($folder, $pathSpec);
			} 
			// or Autocreate some folders to be used
			elseif (in_array($folder, self::$autocreateable, true)) {
				mkdir($pathSpec, 0777, true);
				$this->configureFolder($folder, $pathSpec);
			}
			// or Autocreate some folder with their structure
			elseif (array_key_exists($folder, self::$autocreateable)) {
				mkdir($pathSpec, 0777, true);
				$this->configureFolder($folder, $pathSpec);
				//
				foreach (self::$autocreateable[$folder] as $subfolder) {
					$subFolderSpec = $pathSpec . DIRECTORY_SEPARATOR . $subfolder;
					mkdir($subFolderSpec, 0777, true);
					$this->configureFolder($subfolder, $subFolderSpec);
				}
			}
		}
	}

	/**
	 * Returns the path for the given $folder
	 *
	 * @param string $name
	 * @return string|null;
	 */
	public function getFolder(string $name)
	{
		return $this->configuredFolder[$name] ?? null;
	}

	/**
	 * Register the class autoloader for the application
	 *
	 * @return void;
	 */
	protected function registerAutoloader()
	{
		$this->autoloader = Autoloader::register();
	}

	/**
	 * Loads config files for the application 
	 *
	 * @return void;
	 */
	protected function loadConfigFiles()
	{
		if ($configPath = $this->getFolder('config')) {
			$files = array_diff(@scandir($configPath), ['.','..']);
		}
		//
		if ($files) {
			foreach ($files as $file) {
				if (strcasecmp('.php', substr($file, -4)) === 0) {
					require $configPath . DIRECTORY_SEPARATOR . $file;
				}
			}
		}
	}

	/**
	 * Prepares the application for running 
	 *
	 * @return void;
	 */
	protected function configureApplication()
	{
		$this->configureFolders();
		$this->registerAutoloader();
		$this->loadConfigFiles();
	}

	/**
	 * Initializes the application
	 *
	 * @param string $name
	 * @param string $baseDir
	 */
	public function __construct(string $name, string $baseDir)
	{
		$this->name = $name;
		$this->baseDir = $baseDir;
		//
		$this->configureApplication();
	}

	///////////////////////////////////
	///// STATIC HELPERS //////////////
	///////////////////////////////////

	/**
	 * Returns the application instance
	 *
	 * @return self
	 */
	public static function getInstance()
	{
		return self::$instance;
	}

	/**
	 * Initializes the application
	 *
	 * @static
	 * @param string $name
	 * @param string $baseDir
	 * @return static
	 */
	public static function initialize(string $name, string $baseDir)
	{
		return (self::$instance = new static($name, $baseDir));
	}

}

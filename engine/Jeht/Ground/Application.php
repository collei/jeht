<?php
namespace Jeht\Ground;

use Jeht\Interfaces\Ground\ApplicationInterface;
use Jeht\Ground\Loaders\Autoloader;
use Jeht\Container\Container;
use Jeht\Routing\Router;

use Jeht\Support\Facades\Facade;

class Application extends Container implements ApplicationInterface
{
	/**
	 * @var @static string[]
	 */
	private static $folders = [
		'app','config','public','resources','storage','test'
	];

	/**
	 * @var @static string[]
	 */
	private static $folderSubfolders = [
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
	 * @var \Jeht\Routing\Router
	 */
	private $router;

	/**
	 * @var string[]
	 */
	private $configuredFolder = [];

	/**
	 * @var string[]
	 */
	private $configFiles = [];

	/**
	 * Register a given folder with the application
	 *
	 * @param string $folder
	 * @param string $relativePath
	 * @return void;
	 */
	protected function configureFolder(string $folder, string $relativePath)
	{
		$path = $this->baseDir . DIRECTORY_SEPARATOR . $relativePath;
		//
		// Recreate folder if it does not exist yet
		if (! is_dir($path)) {
			mkdir($path, 0777, true);
		}
		// Configure it
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
			$this->configureFolder($folder, $folder);
		}
		//
		foreach (self::$folderSubfolders as $folder => $subfolders) {
			foreach ($subfolders as $subfolder) {
				$subRelative = $folder . DIRECTORY_SEPARATOR . $subfolder;
				//
				$this->configureFolder($subfolder, $subRelative);
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

	protected function intiailizeRoutes()
	{
		$filename = $this->configFiles['route'] ?? false;

		if ($filename && file_exists($filename)) {
			$this->router = RouteFacade::registerRoutesAndRetrieveRouter();
		}
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
					$this->configFiles[$file] = (
						$configFile = $configPath . DIRECTORY_SEPARATOR . $file
					);
					//
					echo "<div>Application::loadConfigFiles: $configFile</div>";
					require $configFile;
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
		$this->registerCoreSingletons();
		$this->registerCoreContainerAliases();
		//
		Facade::setFacadeApplication($this);
		//
		$this->loadConfigFiles();
		$this->intiailizeRoutes();
	}

	protected function registerCoreSingletons()
	{
		$routee = $this->make(\Jeht\Routing\Router::class);

		$this->singleton(\Jeht\Routing\Route::class, function() use ($routee){
			return $routee;
		});

		$this->instance(\Jeht\Routing\Router::class, $routee);
		$this->instance(\Jeht\Routing\RouteRegistrar::class, $this->make(\Jeht\Routing\RouteRegistrar::class, [$routee]));
	}

	protected function registerCoreContainerAliases()
	{
		$coreConfigured = [
			'app' => [self::class, \Jeht\Interfaces\Container\Container::class, \Jeht\Interfaces\Ground\Application::class, \Psr\Container\ContainerInterface::class],
			//'route' => [\Jeht\Routing\Route::class],
			//'route.router' => [\Jeht\Routing\Router::class],
			'route.registrar' => [\Jeht\Routing\RouteRegistrar::class],
		];
		//
		foreach ($coreConfigured as $key => $aliases) {
			$this->singleton($key, $aliases[1] ?? $aliases[0]);
			//
			foreach ($aliases as $alias) {
				$this->singleton($key, $alias);
				$this->alias($key, $alias);
			}
		}
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
		static::setInstance($this);
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

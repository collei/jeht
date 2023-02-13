<?php
namespace Jeht\Ground;

use RuntimeException;
use Jeht\Interfaces\Ground\Application as ApplicationInterface;
use Jeht\Ground\Loaders\Autoloader;
use Jeht\Container\Container;
use Jeht\Routing\Router;

use Jeht\Support\Data;
use Jeht\Support\Facades\Facade;

class Application extends Container implements ApplicationInterface
{
	protected $version = 'undefined';
	protected $basePath;

	/**
	 * Get the version number of the application.
	 *
	 * @return string
	 */
	public function version()
	{
		return $this->version;
	}

	/**
	 * Get the base path of the Laravel installation.
	 *
	 * @param  string  $path
	 * @return string
	 */
	public function basePath(string $path = '')
	{
		return $this->basePath . ($path ? DIRECTORY_SEPARATOR.$path : $path);
	}

	/**
	 * Get the path to the bootstrap directory.
	 *
	 * @param  string  $path
	 * @return string
	 */
	public function bootstrapPath($path = '');

	/**
	 * Get the path to the application configuration files.
	 *
	 * @param  string  $path
	 * @return string
	 */
	public function configPath($path = '');

	/**
	 * Get the path to the database directory.
	 *
	 * @param  string  $path
	 * @return string
	 */
	public function databasePath($path = '');

	/**
	 * Get the path to the resources directory.
	 *
	 * @param  string  $path
	 * @return string
	 */
	public function resourcePath($path = '');

	/**
	 * Get the path to the storage directory.
	 *
	 * @return string
	 */
	public function storagePath();

	/**
	 * Get or check the current application environment.
	 *
	 * @param  string|array  $environments
	 * @return string|bool
	 */
	public function environment(...$environments);

	/**
	 * Determine if the application is running in the console.
	 *
	 * @return bool
	 */
	public function runningInConsole();

	/**
	 * Determine if the application is running unit tests.
	 *
	 * @return bool
	 */
	public function runningUnitTests();

	/**
	 * Determine if the application is currently down for maintenance.
	 *
	 * @return bool
	 */
	public function isDownForMaintenance();

	/**
	 * Register all of the configured providers.
	 *
	 * @return void
	 */
	public function registerConfiguredProviders();

	/**
	 * Register a service provider with the application.
	 *
	 * @param  \Jeht\Support\ServiceProvider|string  $provider
	 * @param  bool  $force
	 * @return \Jeht\Support\ServiceProvider
	 */
	public function register($provider, $force = false);

	/**
	 * Register a deferred provider and service.
	 *
	 * @param  string  $provider
	 * @param  string|null  $service
	 * @return void
	 */
	public function registerDeferredProvider($provider, $service = null);

	/**
	 * Resolve a service provider instance from the class name.
	 *
	 * @param  string  $provider
	 * @return \Jeht\Support\ServiceProvider
	 */
	public function resolveProvider($provider);

	/**
	 * Boot the application's service providers.
	 *
	 * @return void
	 */
	public function boot();

	/**
	 * Register a new boot listener.
	 *
	 * @param  callable  $callback
	 * @return void
	 */
	public function booting($callback);

	/**
	 * Register a new "booted" listener.
	 *
	 * @param  callable  $callback
	 * @return void
	 */
	public function booted($callback);

	/**
	 * Run the given array of bootstrap classes.
	 *
	 * @param  array  $bootstrappers
	 * @return void
	 */
	public function bootstrapWith(array $bootstrappers);

	/**
	 * Get the current application locale.
	 *
	 * @return string
	 */
	public function getLocale();

	/**
	 * Get the application namespace.
	 *
	 * @return string
	 *
	 * @throws \RuntimeException
	 */
	public function getNamespace();

	/**
	 * Get the registered service provider instances if any exist.
	 *
	 * @param  \Jeht\Support\ServiceProvider|string  $provider
	 * @return array
	 */
	public function getProviders($provider);

	/**
	 * Determine if the application has been bootstrapped before.
	 *
	 * @return bool
	 */
	public function hasBeenBootstrapped();

	/**
	 * Load and boot all of the remaining deferred providers.
	 *
	 * @return void
	 */
	public function loadDeferredProviders();

	/**
	 * Set the current application locale.
	 *
	 * @param  string  $locale
	 * @return void
	 */
	public function setLocale($locale);

	/**
	 * Determine if middleware has been disabled for the application.
	 *
	 * @return bool
	 */
	public function shouldSkipMiddleware();

	/**
	 * Terminate the application.
	 *
	 * @return void
	 */
	public function terminate();










	/**
	 * @var @static string[]
	 */
	private static $folders = [
		'app','config','public','resources','routes','storage','test'
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
	private $basePath;

	/**
	 * @var string
	 */
	private $appPath;

	/**
	 * @var string
	 */
	private $kernelPath;

	/**
	 * @var string
	 */
	private $namespace;

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
	 * Initializes the application
	 *
	 * @param string $name
	 * @param string $basePath
	 */
	public function __construct(string $name, string $basePath)
	{
		$this->name = $name;
		//
		$this->detectKernelPath();
		//
		if (! $this->basePath) {
			$this->setBasePath($basePath);
		}
		//
		$this->registerAutoloader();
		$this->registerBaseBindings();
		$this->registerCoreContainerAliases();

		$this->loadConfigFiles();
		$this->registerCoreSingletons();
		$this->intiailizeRoutes();
	}

	protected function detectKernelPath()
	{
		$this->kernelPath = dirname(__DIR__, 3);
	}

	protected function detectAppRootUri()
	{
		list($basePath, $docRoot) = str_replace(
			'\\', '/', [$this->basePath, $_SERVER['DOCUMENT_ROOT']]
		);
		//
		$appRootUri = str_replace($docRoot, '', $basePath);
		//
		$this->appRootUri = '/' . trim($appRootUri, '/');
		//
		$this->instance('app.rooturi', $this->appRootUri);
	}

	/**
	 * Register the class autoloader for the application
	 *
	 * @return void;
	 */
	protected function registerAutoloader()
	{
		$this->autoloader = Autoloader::register(
			$this->namespace, $this->basePath
		);
	}

	/**
	 * Set the application basepath 
	 *
	 * @param string $basePath
	 * @return this
	 */
	protected function setBasePath(string $basePath)
	{
		$this->basePath = rtrim($basePath, '\/');
		//
		$this->appPath = $this->basePath.DIRECTORY_SEPARATOR.'app';
		//
		$this->namespace = $this->getNamespace();
		//
		$this->configureFolders();
		$this->detectAppRootUri();
	}

	/**
	 * Get the application basepath 
	 *
	 * @param string $path
	 * @return string
	 */
	public function path(string $path = '')
	{
		$appPath = $this->appPath ?: ($this->basePath.DIRECTORY_SEPARATOR.'app');
		//
		return $appPath . ($path ? DIRECTORY_SEPARATOR.$path : $path);
	}

	/**
	 * Get the framework kernel path 
	 *
	 * @param string $path
	 * @return string
	 */
	public function kernelPath(string $path = '')
	{
		return $this->kernelPath . ($path ? DIRECTORY_SEPARATOR . $path : $path);
	}

	/**
	 * Get the application basepath 
	 *
	 * @param string $path
	 * @return string
	 */
	public function appPath(string $path = '')
	{
		return $this->appPath . ($path ? DIRECTORY_SEPARATOR . $path : $path);
	}

	/**
	 * Get the application config path 
	 *
	 * @param string $path
	 * @return string
	 */
	public function configPath(string $path = '')
	{
		if (! isset($this->configuredFolder['app'])) {
			$this->configuredFolder('config', 'config');
		}
		//
		return $this->configuredFolder['app'] . (
			$path ? DIRECTORY_SEPARATOR.$path : $path
		);
	}

	/**
	 * Register a given folder with the application
	 *
	 * @param string $folder
	 * @param string $relativePath
	 * @return void;
	 */
	protected function configureFolder(string $folder, string $relativePath)
	{
		$path = $this->basePath . DIRECTORY_SEPARATOR . $relativePath;
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
	 * Returns the path for the given $folder
	 *
	 * @param string $name
	 * @return string|null;
	 */
	protected function registerBaseBindings()
	{
		static::setInstance($this);
		//
		Facade::setFacadeApplication($this);
		//
		$this->instance('app', $this);
		$this->instance(Application::class, $this);
		$this->instance(Container::class, $this);
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
					require_once $configFile;
				}
			}
		}
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
			'route' => [\Jeht\Routing\Route::class],
			'route.router' => [\Jeht\Routing\Router::class],
			'route.registrar' => [\Jeht\Routing\RouteRegistrar::class],
		];
		//
		foreach ($coreConfigured as $key => $aliases) {
			foreach ($aliases as $alias) {
				$this->alias($alias, $key);//, $alias);
			}
		}
	}

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
	 * @param string $basePath
	 * @return static
	 */
	public static function initialize(string $name, string $basePath)
	{
		return (self::$instance = new static($name, $basePath));
	}



	/**
	 * Get the application namespace.
	 *
	 * @return string
	 */
	public function getNamespace()
	{
		if (! is_null($this->namespace)) {
			return $this->namespace;
		}
		//
		return $this->namespace = 'App\\';
	}

}

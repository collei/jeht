<?php
namespace Jeht\Ground;

use Closure;
use Jeht\Container\Container;
use Jeht\Interfaces\Ground\Application as ApplicationInterface;
use Jeht\Interfaces\Ground\CachesConfiguration;
use Jeht\Interfaces\Ground\CachesRoutes;
use Jeht\Ground\Loaders\Autoloader;
use Jeht\Ground\Loaders\AliasLoader;
use Jeht\Routing\RoutingServiceProvider;
use Jeht\Collections\Collection;
use Jeht\Filesystem\Filesystem;
use Jeht\Filesystem\FolderTreeCreator;
use Jeht\Support\Arr;
use Jeht\Support\Str;
use Jeht\Support\Env\Env;
use Jeht\Support\ServiceProvider;

class Application extends Container implements ApplicationInterface, CachesConfiguration, CachesRoutes
{
	/**
	 * The base path for the client webapp.
	 *
	 * @var string
	 */
	protected $basePath;

	/**
	 * The autoloader for the client webapp classes.
	 *
	 * @var \Jeht\Ground\Loaders\Autoloader
	 */
	protected $clientAutoloader;

	/**
	 * Indicates if the application has been bootstrapped before.
	 *
	 * @var bool
	 */
	protected $hasBeenBootstrapped = false;

	/**
	 * Indicates if the application has "booted".
	 *
	 * @var bool
	 */
	protected $booted = false;

	/**
	 * The array of booting callbacks.
	 *
	 * @var callable[]
	 */
	protected $bootingCallbacks = [];

	/**
	 * The array of booted callbacks.
	 *
	 * @var callable[]
	 */
	protected $bootedCallbacks = [];

	/**
	 * The array of terminating callbacks.
	 *
	 * @var callable[]
	 */
	protected $terminatingCallbacks = [];

	/**
	 * All of the registered service providers.
	 *
	 * @var \Jeht\Support\ServiceProvider[]
	 */
	protected $serviceProviders = [];

	/**
	 * The names of the loaded service providers.
	 *
	 * @var array
	 */
	protected $loadedProviders = [];

	/**
	 * The deferred services and their providers.
	 *
	 * @var array
	 */
	protected $deferredServices = [];

	/**
	 * The custom application path defined by the developer.
	 *
	 * @var string
	 */
	protected $appPath;

	/**
	 * The custom database path defined by the developer.
	 *
	 * @var string
	 */
	protected $databasePath;

	/**
	 * The custom language file path defined by the developer.
	 *
	 * @var string
	 */
	protected $langPath;

	/**
	 * The custom storage path defined by the developer.
	 *
	 * @var string
	 */
	protected $storagePath;

	/**
	 * The custom environment path defined by the developer.
	 *
	 * @var string
	 */
	protected $environmentPath;

	/**
	 * The environment file to load during bootstrapping.
	 *
	 * @var string
	 */
	protected $environmentFile = '.env';

	/**
	 * Indicates if the application is running in the console.
	 *
	 * @var bool|null
	 */
	protected $isRunningInConsole;

	/**
	 * The application namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'App\\';

	/**
	 * The prefixes of absolute cache paths for use during normalization.
	 *
	 * @var string[]
	 */
	protected $absoluteCachePathPrefixes = ['/', '\\'];

	/**
	 * The basic structure tree of the client webapp.
	 *
	 * @var array
	 */
	protected $basicFolderTree = [
		'app',
		'bootstrap/cache',
		'config',
		'public',
		'resources',
		'resources/css',
		'resources/js',
		'resources/media',
		'resources/views',
		'routes',
		'storage/logging',
		'storage/cache',
		'test',
	]; 

	/**
	 * Create a new webapp kernel application instance.
	 *
	 * @param  string|null  $basePath
	 * @return void
	 */
	public function __construct($basePath = null)
	{
		$this->initWebappTree($basePath);
		//
		$this->registerBaseBindings();
		$this->registerBaseServiceProviders();
		$this->registerCoreContainerAliases();
	}

	/**
	 * Initializes the webapp tree and the autoloader
	 *
	 * @param  string|null  $basePath
	 * @return void
	 */
	protected function initWebappTree(string $basePath = null)
	{
		$this->detectKernelPath();
		//
		if ($basePath) {
			$this->setBasePath($basePath);
		}
		//
		$this->detectAppRootUri();
		$this->registerClientAutoloader();
		//
		FolderTreeCreator::for($this->basicFolderTree)
			->in($this['app.rooturi'])
			->create();
	}

	/**
	 * Get the version number of the application.
	 *
	 * @return string
	 */
	public function version()
	{
		return $this['app.version'] ?? 'undefined';
	}

	/**
	 * Register the basic bindings into the container.
	 *
	 * @return void
	 */
	protected function registerBaseBindings()
	{
		static::setInstance($this);
		//
		$this->instance('app', $this);
		//
		$this->instance(Container::class, $this);
		$this->singleton(Mix::class);
		//
		$this->singleton(PackageManifest::class, function () {
			return new PackageManifest(
				new Filesystem, $this->basePath(), $this->getCachedPackagesPath()
			);
		});
	}

	/**
	 * Register all of the base service providers.
	 *
	 * @return void
	 */
	protected function registerBaseServiceProviders()
	{
		//$this->register(new EventServiceProvider($this));
		//$this->register(new LogServiceProvider($this));
		$this->register(new RoutingServiceProvider($this));
	}

	/**
	 * Run the given array of bootstrap classes.
	 *
	 * @param  string[]  $bootstrappers
	 * @return void
	 */
	public function bootstrapWith(array $bootstrappers)
	{
		$this->hasBeenBootstrapped = true;

		foreach ($bootstrappers as $bootstrapper) {
			//$this['events']->dispatch('bootstrapping: '.$bootstrapper, [$this]);

			$this->make($bootstrapper)->bootstrap($this);

			//$this['events']->dispatch('bootstrapped: '.$bootstrapper, [$this]);
		}
	}

	/**
	 * Register a callback to run after loading the environment.
	 *
	 * @param  \Closure  $callback
	 * @return void
	 */
	public function afterLoadingEnvironment(Closure $callback)
	{
		$this->afterBootstrapping(
			LoadEnvironmentVariables::class, $callback
		);
	}

	/**
	 * Register a callback to run before a bootstrapper.
	 *
	 * @param  string  $bootstrapper
	 * @param  \Closure  $callback
	 * @return void
	 */
	public function beforeBootstrapping($bootstrapper, Closure $callback)
	{
		//$this['events']->listen('bootstrapping: '.$bootstrapper, $callback);
	}

	/**
	 * Register a callback to run after a bootstrapper.
	 *
	 * @param  string  $bootstrapper
	 * @param  \Closure  $callback
	 * @return void
	 */
	public function afterBootstrapping($bootstrapper, Closure $callback)
	{
		//$this['events']->listen('bootstrapped: '.$bootstrapper, $callback);
	}

	/**
	 * Determine if the application has been bootstrapped before.
	 *
	 * @return bool
	 */
	public function hasBeenBootstrapped()
	{
		return $this->hasBeenBootstrapped;
	}

	/**
	 * Set the base path for the application.
	 *
	 * @param  string  $basePath
	 * @return $this
	 */
	public function setBasePath($basePath)
	{
		$this->basePath = rtrim($basePath, '\/');
		//
		$this->bindPathsInContainer();
		//
		return $this;
	}

	/**
	 * Detects the Jeht Kernel path
	 *
	 * @return void
	 */
	protected function detectKernelPath()
	{
		$this->kernelPath = dirname(__DIR__, 3);
	}

	/**
	 * Detects the client webapp root URI
	 *
	 * @return void
	 */
	protected function detectAppRootUri()
	{
		list($basePath, $docRoot) = str_replace(
			'\\', '/', array($this->basePath, $_SERVER['DOCUMENT_ROOT'])
		);
		//
		$appRootUri = '/' . trim(str_replace($docRoot, '', $basePath), '/');
		//
		$this->instance('app.rooturi', $this->appRootUri = $appRootUri);
	}

	/**
	 * Initializes the autoloader for the client webapp.
	 *
	 * @return void
	 */
	protected function registerClientAutoloader()
	{
		if (! $this->clientAutoloader) {
			$this->clientAutoloader = Autoloader::register(
				$this->getNamespace(), $this['path.base']
			);
		}
	}

	/**
	 * Bind all of the application paths in the container.
	 *
	 * @return void
	 */
	protected function bindPathsInContainer()
	{
		$this->instance('path', $this->path());
		$this->instance('path.base', $this->basePath());
		$this->instance('path.lang', $this->langPath());
		$this->instance('path.config', $this->configPath());
		$this->instance('path.public', $this->publicPath());
		$this->instance('path.storage', $this->storagePath());
		$this->instance('path.database', $this->databasePath());
		$this->instance('path.resources', $this->resourcePath());
		$this->instance('path.bootstrap', $this->bootstrapPath());
	}

	/**
	 * Get the path to the application "app" directory.
	 *
	 * @param  string  $path
	 * @return string
	 */
	public function path($path = '')
	{
		$appPath = $this->appPath ?: $this->basePath.DIRECTORY_SEPARATOR.'app';

		return $appPath.($path ? DIRECTORY_SEPARATOR.$path : $path);
	}

	/**
	 * Set the application directory.
	 *
	 * @param  string  $path
	 * @return $this
	 */
	public function useAppPath($path)
	{
		$this->appPath = $path;

		$this->instance('path', $path);

		return $this;
	}

	/**
	 * Get the base path of the Laravel installation.
	 *
	 * @param  string  $path
	 * @return string
	 */
	public function basePath($path = '')
	{
		return $this->basePath.($path ? DIRECTORY_SEPARATOR.$path : $path);
	}

	/**
	 * Get the path to the bootstrap directory.
	 *
	 * @param  string  $path
	 * @return string
	 */
	public function bootstrapPath($path = '')
	{
		return $this->basePath.DIRECTORY_SEPARATOR.'bootstrap'.($path ? DIRECTORY_SEPARATOR.$path : $path);
	}

	/**
	 * Get the path to the application configuration files.
	 *
	 * @param  string  $path
	 * @return string
	 */
	public function configPath($path = '')
	{
		return $this->basePath.DIRECTORY_SEPARATOR.'config'.($path ? DIRECTORY_SEPARATOR.$path : $path);
	}

	/**
	 * Get the path to the database directory.
	 *
	 * @param  string  $path
	 * @return string
	 */
	public function databasePath($path = '')
	{
		return ($this->databasePath ?: $this->basePath.DIRECTORY_SEPARATOR.'database').($path ? DIRECTORY_SEPARATOR.$path : $path);
	}

	/**
	 * Set the database directory.
	 *
	 * @param  string  $path
	 * @return $this
	 */
	public function useDatabasePath($path)
	{
		$this->databasePath = $path;

		$this->instance('path.database', $path);

		return $this;
	}

	/**
	 * Get the path to the language files.
	 *
	 * @return string
	 */
	public function langPath()
	{
		if ($this->langPath) {
			return $this->langPath;
		}

		if (is_dir($path = $this->resourcePath().DIRECTORY_SEPARATOR.'lang')) {
			return $path;
		}

		return $this->basePath().DIRECTORY_SEPARATOR.'lang';
	}

	/**
	 * Set the language file directory.
	 *
	 * @param  string  $path
	 * @return $this
	 */
	public function useLangPath($path)
	{
		$this->langPath = $path;

		$this->instance('path.lang', $path);

		return $this;
	}

	/**
	 * Get the path to the public / web directory.
	 *
	 * @return string
	 */
	public function publicPath()
	{
		return $this->basePath.DIRECTORY_SEPARATOR.'public';
	}

	/**
	 * Get the path to the storage directory.
	 *
	 * @return string
	 */
	public function storagePath()
	{
		return $this->storagePath ?: $this->basePath.DIRECTORY_SEPARATOR.'storage';
	}

	/**
	 * Set the storage directory.
	 *
	 * @param  string  $path
	 * @return $this
	 */
	public function useStoragePath($path)
	{
		$this->storagePath = $path;

		$this->instance('path.storage', $path);

		return $this;
	}

	/**
	 * Get the path to the resources directory.
	 *
	 * @param  string  $path
	 * @return string
	 */
	public function resourcePath($path = '')
	{
		return $this->basePath.DIRECTORY_SEPARATOR.'resources'.($path ? DIRECTORY_SEPARATOR.$path : $path);
	}

	/**
	 * Get the path to the views directory.
	 *
	 * This method returns the first configured path in the array of view paths.
	 *
	 * @param  string  $path
	 * @return string
	 */
	public function viewPath($path = '')
	{
		$basePath = $this['config']->get('view.paths')[0];

		return rtrim($basePath, DIRECTORY_SEPARATOR).($path ? DIRECTORY_SEPARATOR.$path : $path);
	}

	/**
	 * Get the path to the environment file directory.
	 *
	 * @return string
	 */
	public function environmentPath()
	{
		return $this->environmentPath ?: $this->basePath;
	}

	/**
	 * Set the directory for the environment file.
	 *
	 * @param  string  $path
	 * @return $this
	 */
	public function useEnvironmentPath($path)
	{
		$this->environmentPath = $path;

		return $this;
	}

	/**
	 * Set the environment file to be loaded during bootstrapping.
	 *
	 * @param  string  $file
	 * @return $this
	 */
	public function loadEnvironmentFrom($file)
	{
		$this->environmentFile = $file;

		return $this;
	}

	/**
	 * Get the environment file the application is using.
	 *
	 * @return string
	 */
	public function environmentFile()
	{
		return $this->environmentFile ?: '.env';
	}

	/**
	 * Get the fully qualified path to the environment file.
	 *
	 * @return string
	 */
	public function environmentFilePath()
	{
		return $this->environmentPath().DIRECTORY_SEPARATOR.$this->environmentFile();
	}

	/**
	 * Get or check the current application environment.
	 *
	 * @param  string|array  $environments
	 * @return string|bool
	 */
	public function environment(...$environments)
	{
		if (count($environments) > 0) {
			$patterns = is_array($environments[0]) ? $environments[0] : $environments;

			return Str::is($patterns, $this['env']);
		}

		return $this['env'];
	}

	/**
	 * Determine if the application is in the local environment.
	 *
	 * @return bool
	 */
	public function isLocal()
	{
		return $this['env'] === 'local';
	}

	/**
	 * Determine if the application is in the production environment.
	 *
	 * @return bool
	 */
	public function isProduction()
	{
		return $this['env'] === 'production';
	}

	/**
	 * Detect the application's current environment.
	 *
	 * @param  \Closure  $callback
	 * @return string
	 */
	public function detectEnvironment(Closure $callback)
	{
		$args = $_SERVER['argv'] ?? null;

		return $this['env'] = (new EnvironmentDetector)->detect($callback, $args);
	}

	/**
	 * Determine if the application is running in the console.
	 *
	 * @return bool
	 */
	public function runningInConsole()
	{
		if ($this->isRunningInConsole === null) {
			$this->isRunningInConsole = Env::get('APP_RUNNING_IN_CONSOLE') ?? (\PHP_SAPI === 'cli' || \PHP_SAPI === 'phpdbg');
		}

		return $this->isRunningInConsole;
	}

	/**
	 * Determine if the application is running unit tests.
	 *
	 * @return bool
	 */
	public function runningUnitTests()
	{
		return $this->bound('env') && $this['env'] === 'testing';
	}

	/**
	 * Determine if the application is running with debug mode enabled.
	 *
	 * @return bool
	 */
	public function hasDebugModeEnabled()
	{
		return (bool) $this['config']->get('app.debug');
	}

	/**
	 * Register all of the configured providers.
	 *
	 * @return void
	 */
	public function registerConfiguredProviders()
	{
		$providers = Collection::make($this->make('config')->get('app.providers'))
						->partition(function ($provider) {
							// Try load any webapp client class providers
							if (strpos($provider, 'App\\') === 0) {
								$exists = class_exists($provider, true);
							}
							//
							return strpos($provider, 'Jeht\\') === 0;
						});

		$providers->splice(1, 0, [$this->make(PackageManifest::class)->providers()]);

		(new ProviderRepository($this, new Filesystem, $this->getCachedServicesPath()))
					->load($providers->collapse()->toArray());
	}

	/**
	 * Register a service provider with the application.
	 *
	 * @param  \Jeht\Support\ServiceProvider|string  $provider
	 * @param  bool  $force
	 * @return \Jeht\Support\ServiceProvider
	 */
	public function register($provider, $force = false)
	{
		if (($registered = $this->getProvider($provider)) && ! $force) {
			return $registered;
		}

		// If the given "provider" is a string, we will resolve it, passing in the
		// application instance automatically for the developer. This is simply
		// a more convenient way of specifying your service provider classes.
		if (is_string($provider)) {
			$provider = $this->resolveProvider($provider);
		}

		$provider->register();

		// If there are bindings / singletons set as properties on the provider we
		// will spin through them and register them with the application, which
		// serves as a convenience layer while registering a lot of bindings.
		if (property_exists($provider, 'bindings')) {
			foreach ($provider->bindings as $key => $value) {
				$this->bind($key, $value);
			}
		}

		if (property_exists($provider, 'singletons')) {
			foreach ($provider->singletons as $key => $value) {
				$this->singleton($key, $value);
			}
		}

		$this->markAsRegistered($provider);

		// If the application has already booted, we will call this boot method on
		// the provider class so it has an opportunity to do its boot logic and
		// will be ready for any usage by this developer's application logic.
		if ($this->isBooted()) {
			$this->bootProvider($provider);
		}

		return $provider;
	}

	/**
	 * Get the registered service provider instance if it exists.
	 *
	 * @param  \Jeht\Support\ServiceProvider|string  $provider
	 * @return \Jeht\Support\ServiceProvider|null
	 */
	public function getProvider($provider)
	{
		return array_values($this->getProviders($provider))[0] ?? null;
	}

	/**
	 * Get the registered service provider instances if any exist.
	 *
	 * @param  \Jeht\Support\ServiceProvider|string  $provider
	 * @return array
	 */
	public function getProviders($provider)
	{
		$name = is_string($provider) ? $provider : get_class($provider);

		return Arr::where($this->serviceProviders, function ($value) use ($name) {
			return $value instanceof $name;
		});
	}

	/**
	 * Resolve a service provider instance from the class name.
	 *
	 * @param  string  $provider
	 * @return \Jeht\Support\ServiceProvider
	 */
	public function resolveProvider($provider)
	{
		return new $provider($this);
	}

	/**
	 * Mark the given provider as registered.
	 *
	 * @param  \Jeht\Support\ServiceProvider  $provider
	 * @return void
	 */
	protected function markAsRegistered($provider)
	{
		$this->serviceProviders[] = $provider;

		$this->loadedProviders[get_class($provider)] = true;
	}

	/**
	 * Load and boot all of the remaining deferred providers.
	 *
	 * @return void
	 */
	public function loadDeferredProviders()
	{
		// We will simply spin through each of the deferred providers and register each
		// one and boot them if the application has booted. This should make each of
		// the remaining services available to this application for immediate use.
		foreach ($this->deferredServices as $service => $provider) {
			$this->loadDeferredProvider($service);
		}

		$this->deferredServices = [];
	}

	/**
	 * Load the provider for a deferred service.
	 *
	 * @param  string  $service
	 * @return void
	 */
	public function loadDeferredProvider($service)
	{
		if (! $this->isDeferredService($service)) {
			return;
		}

		$provider = $this->deferredServices[$service];

		// If the service provider has not already been loaded and registered we can
		// register it with the application and remove the service from this list
		// of deferred services, since it will already be loaded on subsequent.
		if (! isset($this->loadedProviders[$provider])) {
			$this->registerDeferredProvider($provider, $service);
		}
	}

	/**
	 * Register a deferred provider and service.
	 *
	 * @param  string  $provider
	 * @param  string|null  $service
	 * @return void
	 */
	public function registerDeferredProvider($provider, $service = null)
	{
		// Once the provider that provides the deferred service has been registered we
		// will remove it from our local list of the deferred services with related
		// providers so that this container does not try to resolve it out again.
		if ($service) {
			unset($this->deferredServices[$service]);
		}

		$this->register($instance = new $provider($this));

		if (! $this->isBooted()) {
			$this->booting(function () use ($instance) {
				$this->bootProvider($instance);
			});
		}
	}

	/**
	 * Resolve the given type from the container.
	 *
	 * @param  string  $abstract
	 * @param  array  $parameters
	 * @return mixed
	 */
	public function make($abstract, array $parameters = [])
	{
		$this->loadDeferredProviderIfNeeded($abstract = $this->getAlias($abstract));

		return parent::make($abstract, $parameters);
	}

	/**
	 * Resolve the given type from the container.
	 *
	 * @param  string  $abstract
	 * @param  array  $parameters
	 * @param  bool  $raiseEvents
	 * @return mixed
	 */
	protected function resolve($abstract, $parameters = [], $raiseEvents = true)
	{
		$this->loadDeferredProviderIfNeeded($abstract = $this->getAlias($abstract));

		return parent::resolve($abstract, $parameters, $raiseEvents);
	}

	/**
	 * Load the deferred provider if the given type is a deferred service and the instance has not been loaded.
	 *
	 * @param  string  $abstract
	 * @return void
	 */
	protected function loadDeferredProviderIfNeeded($abstract)
	{
		if ($this->isDeferredService($abstract) && ! isset($this->instances[$abstract])) {
			$this->loadDeferredProvider($abstract);
		}
	}

	/**
	 * Determine if the given abstract type has been bound.
	 *
	 * @param  string  $abstract
	 * @return bool
	 */
	public function bound($abstract)
	{
		return $this->isDeferredService($abstract) || parent::bound($abstract);
	}

	/**
	 * Determine if the application has booted.
	 *
	 * @return bool
	 */
	public function isBooted()
	{
		return $this->booted;
	}

	/**
	 * Boot the application's service providers.
	 *
	 * @return void
	 */
	public function boot()
	{
		if ($this->isBooted()) {
			return;
		}

		// Once the application has booted we will also fire some "booted" callbacks
		// for any listeners that need to do work after this initial booting gets
		// finished. This is useful when ordering the boot-up processes we run.
		$this->fireAppCallbacks($this->bootingCallbacks);

		array_walk($this->serviceProviders, function ($p) {
			$this->bootProvider($p);
		});

		$this->booted = true;

		$this->fireAppCallbacks($this->bootedCallbacks);
	}

	/**
	 * Boot the given service provider.
	 *
	 * @param  \Jeht\Support\ServiceProvider  $provider
	 * @return void
	 */
	protected function bootProvider(ServiceProvider $provider)
	{
		$provider->callBootingCallbacks();

		if (method_exists($provider, 'boot')) {
			$this->call([$provider, 'boot']);
		}

		$provider->callBootedCallbacks();
	}

	/**
	 * Register a new boot listener.
	 *
	 * @param  callable  $callback
	 * @return void
	 */
	public function booting($callback)
	{
		$this->bootingCallbacks[] = $callback;
	}

	/**
	 * Register a new "booted" listener.
	 *
	 * @param  callable  $callback
	 * @return void
	 */
	public function booted($callback)
	{
		$this->bootedCallbacks[] = $callback;

		if ($this->isBooted()) {
			$callback($this);
		}
	}

	/**
	 * Call the booting callbacks for the application.
	 *
	 * @param  callable[]  $callbacks
	 * @return void
	 */
	protected function fireAppCallbacks(array &$callbacks)
	{
		$index = 0;

		while ($index < count($callbacks)) {
			$callbacks[$index]($this);

			$index++;
		}
	}

	/**
	 * Determine if middleware has been disabled for the application.
	 *
	 * @return bool
	 */
	public function shouldSkipMiddleware()
	{
		return $this->bound('middleware.disable') &&
			   $this->make('middleware.disable') === true;
	}

	/**
	 * Get the path to the cached services.php file.
	 *
	 * @return string
	 */
	public function getCachedServicesPath()
	{
		return $this->normalizeCachePath('APP_SERVICES_CACHE', 'cache/services.php');
	}

	/**
	 * Get the path to the cached packages.php file.
	 *
	 * @return string
	 */
	public function getCachedPackagesPath()
	{
		return $this->normalizeCachePath('APP_PACKAGES_CACHE', 'cache/packages.php');
	}

	/**
	 * Determine if the application configuration is cached.
	 *
	 * @return bool
	 */
	public function configurationIsCached()
	{
		return is_file($this->getCachedConfigPath());
	}

	/**
	 * Get the path to the configuration cache file.
	 *
	 * @return string
	 */
	public function getCachedConfigPath()
	{
		return $this->normalizeCachePath('APP_CONFIG_CACHE', 'cache/config.php');
	}

	/**
	 * Determine if the application routes are cached.
	 *
	 * @return bool
	 */
	public function routesAreCached()
	{
		return false; //$this['files']->exists($this->getCachedRoutesPath());
	}

	/**
	 * Get the path to the routes cache file.
	 *
	 * @return string
	 */
	public function getCachedRoutesPath()
	{
		return $this->normalizeCachePath('APP_ROUTES_CACHE', 'cache/routes-v7.php');
	}

	/**
	 * Determine if the application events are cached.
	 *
	 * @return bool
	 */
	public function eventsAreCached()
	{
		return $this['files']->exists($this->getCachedEventsPath());
	}

	/**
	 * Get the path to the events cache file.
	 *
	 * @return string
	 */
	public function getCachedEventsPath()
	{
		return $this->normalizeCachePath('APP_EVENTS_CACHE', 'cache/events.php');
	}

	/**
	 * Normalize a relative or absolute path to a cache file.
	 *
	 * @param  string  $key
	 * @param  string  $default
	 * @return string
	 */
	protected function normalizeCachePath($key, $default)
	{
		if (is_null($env = Env::get($key))) {
			return $this->bootstrapPath($default);
		}

		return Str::startsWith($env, $this->absoluteCachePathPrefixes)
				? $env
				: $this->basePath($env);
	}

	/**
	 * Add new prefix to list of absolute path prefixes.
	 *
	 * @param  string  $prefix
	 * @return $this
	 */
	public function addAbsoluteCachePathPrefix($prefix)
	{
		$this->absoluteCachePathPrefixes[] = $prefix;

		return $this;
	}

	/**
	 * Determine if the application is currently down for maintenance.
	 *
	 * @return bool
	 */
	public function isDownForMaintenance()
	{
		return file_exists($this->storagePath().'/framework/down');
	}

	/**
	 * Throw an HttpException with the given data.
	 *
	 * @param  int  $code
	 * @param  string  $message
	 * @param  array  $headers
	 * @return never
	 *
	 * @throws \Jeht\Exceptions\Http\HttpException
	 * @throws \Jeht\Exceptions\Http\NotFoundHttpException
	 */
	public function abort($code, $message = '', array $headers = [])
	{
		if ($code == 404) {
			throw new NotFoundHttpException($message);
		}

		throw new HttpException($code, $message, null, $headers);
	}

	/**
	 * Register a terminating callback with the application.
	 *
	 * @param  callable|string  $callback
	 * @return $this
	 */
	public function terminating($callback)
	{
		$this->terminatingCallbacks[] = $callback;

		return $this;
	}

	/**
	 * Terminate the application.
	 *
	 * @return void
	 */
	public function terminate()
	{
		$index = 0;

		while ($index < count($this->terminatingCallbacks)) {
			$this->call($this->terminatingCallbacks[$index]);

			$index++;
		}
	}

	/**
	 * Get the service providers that have been loaded.
	 *
	 * @return array
	 */
	public function getLoadedProviders()
	{
		return $this->loadedProviders;
	}

	/**
	 * Determine if the given service provider is loaded.
	 *
	 * @param  string  $provider
	 * @return bool
	 */
	public function providerIsLoaded(string $provider)
	{
		return isset($this->loadedProviders[$provider]);
	}

	/**
	 * Get the application's deferred services.
	 *
	 * @return array
	 */
	public function getDeferredServices()
	{
		return $this->deferredServices;
	}

	/**
	 * Set the application's deferred services.
	 *
	 * @param  array  $services
	 * @return void
	 */
	public function setDeferredServices(array $services)
	{
		$this->deferredServices = $services;
	}

	/**
	 * Add an array of services to the application's deferred services.
	 *
	 * @param  array  $services
	 * @return void
	 */
	public function addDeferredServices(array $services)
	{
		$this->deferredServices = array_merge($this->deferredServices, $services);
	}

	/**
	 * Determine if the given service is a deferred service.
	 *
	 * @param  string  $service
	 * @return bool
	 */
	public function isDeferredService($service)
	{
		return isset($this->deferredServices[$service]);
	}

	/**
	 * Configure the real-time facade namespace.
	 *
	 * @param  string  $namespace
	 * @return void
	 */
	public function provideFacades($namespace)
	{
		AliasLoader::setFacadeNamespace($namespace);
	}

	/**
	 * Get the current application locale.
	 *
	 * @return string
	 */
	public function getLocale()
	{
		return $this['config']->get('app.locale');
	}

	/**
	 * Get the current application locale.
	 *
	 * @return string
	 */
	public function currentLocale()
	{
		return $this->getLocale();
	}

	/**
	 * Get the current application fallback locale.
	 *
	 * @return string
	 */
	public function getFallbackLocale()
	{
		return $this['config']->get('app.fallback_locale');
	}

	/**
	 * Set the current application locale.
	 *
	 * @param  string  $locale
	 * @return void
	 */
	public function setLocale($locale)
	{
		$this['config']->set('app.locale', $locale);

		//$this['translator']->setLocale($locale);

		//$this['events']->dispatch(new LocaleUpdated($locale));
	}

	/**
	 * Set the current application fallback locale.
	 *
	 * @param  string  $fallbackLocale
	 * @return void
	 */
	public function setFallbackLocale($fallbackLocale)
	{
		$this['config']->set('app.fallback_locale', $fallbackLocale);

		//$this['translator']->setFallback($fallbackLocale);
	}

	/**
	 * Determine if the application locale is the given locale.
	 *
	 * @param  string  $locale
	 * @return bool
	 */
	public function isLocale($locale)
	{
		return $this->getLocale() == $locale;
	}

	/**
	 * Register the core class aliases in the container.
	 *
	 * @return void
	 */
	public function registerCoreContainerAliases()
	{
		foreach ([
			'app' => [self::class, \Jeht\Interfaces\Ground\Application::class, \Jeht\Interfaces\Container\Container::class, \Psr\Container\ContainerInterface::class],
			'config' => [\Jeht\Config\Repository::class, \Jeht\Interfaces\Config\Repository::class],
			'encrypter' => [\Jeht\Encryption\Encrypter::class, \Jeht\Interfaces\Encryption\Encrypter::class, \Jeht\Interfaces\Encryption\StringEncrypter::class],
			'files' => [\Jeht\Filesystem\Filesystem::class],
			'request' => [\Jeht\Http\Request::class, \Jeht\Interfaces\Http\Request::class],
			'router' => [\Jeht\Routing\Router::class], //, \Jeht\Interfaces\Routing\Registrar::class, \Jeht\Interfaces\Routing\BindingRegistrar::class],
			'url' => [\Jeht\Http\UriFactory::class, \Psr\Http\Message\UriInterface::class],
//			'session' => [\Jeht\Session\SessionManager::class],
//			'session.store' => [\Jeht\Session\Store::class, \Jeht\Interfaces\Session\Session::class],
//			'validator' => [\Jeht\Validation\Factory::class, \Jeht\Interfaces\Validation\Factory::class],
//			'view' => [\Jeht\View\Factory::class, \Jeht\Interfaces\View\Factory::class],
		] as $key => $aliases) {
			foreach ($aliases as $alias) {
				$this->alias($key, $alias);
			}
		}
	}

	/**
	 * Flush the container of all bindings and resolved instances.
	 *
	 * @return void
	 */
	public function flush()
	{
		parent::flush();
		//
		$this->buildStack = [];
		$this->loadedProviders = [];
		$this->bootedCallbacks = [];
		$this->bootingCallbacks = [];
		$this->deferredServices = [];
		$this->reboundCallbacks = [];
		$this->serviceProviders = [];
		$this->resolvingCallbacks = [];
		$this->terminatingCallbacks = [];
		$this->beforeResolvingCallbacks = [];
		$this->afterResolvingCallbacks = [];
		$this->globalBeforeResolvingCallbacks = [];
		$this->globalResolvingCallbacks = [];
		$this->globalAfterResolvingCallbacks = [];
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



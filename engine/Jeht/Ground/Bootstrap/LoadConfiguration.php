<?php
namespace Jeht\Ground\Bootstrap;

use Exception;
use Jeht\Interfaces\Ground\Application;
use Jeht\Interfaces\Config\Repository as RepositoryInterface;
use Jeht\Config\Repository;
use Jeht\Filesystem\Folder;
use Jeht\Interfaces\Filesystem\File;

/**
 * Adapted from Laravel's Illuminate\Foundation\Bootstrap\LoadConfiguration
 * @link https://laravel.com/api/8.x/Illuminate/Foundation/Bootstrap/LoadConfiguration.html
 * @link https://github.com/laravel/framework/blob/8.x/src/Illuminate/Foundation/Bootstrap/LoadConfiguration.php
 */
class LoadConfiguration
{
	/**
	 * Bootstrap the given application
	 *
	 * @param \Jeht\Interfaces\Ground\Application $app
	 * @return void
	 */
	public function bootstrap(Application $app)
	{
		$items = [];

		// First we will see if we have a cache configuration file. If we do, we'll load
		// the configuration items from that file so that it is very quick. Otherwise
		// we will need to spin through every configuration file and load them all.
		if (file_exists($cached = $app->getCachedConfigPath())) {
			$items = require $cached;

			$loadedFromCache = true;
		}

		// Next we will spin through all of the configuration files in the configuration
		// directory and load each one into the repository. This will make all of the
		// options available to the developer for use in various parts of this app.
		$app->instance('config', $config = new Repository($items));

		if (! isset($loadedFromCache)) {
			$this->loadConfigurationFiles($app, $config);
		}

		// Finally, we will set the application's environment based on the configuration
		// values that were loaded. We will pass a callback which will be used to get
		// the environment in a web context where an "--env" switch is not present.
		$app->detectEnvironment(function () use ($config) {
			return $config->get('app.env', 'production');
		});

		date_default_timezone_set($config->get('app.timezone', 'UTC'));

		mb_internal_encoding('UTF-8');
	}

	/**
	 * Load the configuration items from all of the files.
	 *
	 * @param \Jeht\Interfaces\Ground\Application $app
	 * @param \Jeht\Interfaces\Config\Repository $repository
	 * @return void
	 * @throws \Exception
	 */
	protected function loadConfigurationFiles(Application $app, RepositoryInterface $repository)
	{
		$files = $this->getConfigurationFiles($app);
		//
		if (! isset($files['app'])) {
			throw new Exception('Unable to load the "app" configuration file.');
		}
		//
		foreach ($files as $key => $path) {
			$repository->set($key, require $path);
		}
	}

	/**
	 * Get all the configuration files for the application.
	 *
	 * @param \Jeht\Interfaces\Ground\Application $app
	 * @return array
	 */
	protected function getConfigurationFiles(Application $app)
	{
		$files = [];
		//
		$configPath = realpath($app->configPath());
		//
		foreach (Folder::for($configPath)->files()->withName('*.php')->get() as $name => $file) {
			$folder = $this->getNestedFolder($file, $configPath);
			//
			$files[$folder.$file->getFileName()] = $file->getPath();
		}
		//
		ksort($files, SORT_NATURAL);
		//
		return $files;
	}

	/**
	 * Get the configuration file nesting path.
	 *
	 * @param \Jeht\Interfaces\Filesystem\File $file
	 * @param string $configPath
	 * @return string
	 */
	protected function getNestedFolder(File $file, $configPath)
	{
		$folder = $file->getFolderPath();
		//
		if ($nested = trim(str_replace($configPath, '', $folder), DIRECTORY_SEPARATOR)) {
			$nested = str_replace(DIRECTORY_SEPARATOR, '.', $nested).'.';
		}
		//
		return $nested;
	}

}


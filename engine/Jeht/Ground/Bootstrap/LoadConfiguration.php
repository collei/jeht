<?php
namespace Jeht\Ground\Bootstrap;

use Exception;
use Jeht\Interfaces\Ground\Application;
use Jeht\Interfaces\Config\Repository as RepositoryInterface;
use Jeht\Ground\Config\Repository;
use Jeht\Filesystem\Folder;
use Jeht\Interfaces\Filesystem\File;

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
		//
		$app->instance('config', $config = new Repository($items));
		//
		$this->loadConfigurationFiles($app, $config);
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
		foreach (Folder::for($configPath)->files()->withName('*.php') as $name => $file) {
			$folder = $this->getNestedFolder($file, $configPath);
			//
			$files[$folder.$file->getBaseName()] = $file->getPath();
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

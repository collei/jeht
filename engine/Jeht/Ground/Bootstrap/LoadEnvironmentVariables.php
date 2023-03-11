<?php
namespace Jeht\Ground\Bootstrap;

use Jeht\Ground\Interfaces\Application;
use Jeht\Support\Env\Env;
use Jeht\Exceptions\Filesystem\FileNotFoundException;

/**
 * Adapted from Laravel's Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables
 * @link https://laravel.com/api/8.x/Illuminate/Foundation/Bootstrap/LoadEnvironmentVariables.html
 * @link https://github.com/laravel/framework/blob/8.x/src/Illuminate/Foundation/Bootstrap/LoadEnvironmentVariables.php
 *
 */
class LoadEnvironmentVariables
{
	/**
	 * Bootstrap the given application.
	 *
	 * @param  \Jeht\Ground\Interfaces\Application  $app
	 * @return void
	 */
	public function bootstrap(Application $app)
	{
		if ($app->configurationIsCached()) {
			return;
		}

		$this->checkForSpecificEnvironmentFile($app);

		try {
			$this->createEnv($app);
		} catch (FileNotFoundException $e) {
			$this->writeErrorAndDie($e);
		}
	}

	/**
	 * Detect if a custom environment file matching the APP_ENV exists.
	 *
	 * @param  \Jeht\Ground\Interfaces\Application  $app
	 * @return void
	 */
	protected function checkForSpecificEnvironmentFile($app)
	{
		$getoptResult = @getopt("", ["env"]);

		if ($app->runningInConsole() && array_key_exists('env', $getoptResult)) {
			if ($this->setEnvironmentFilePath(
				$app, $app->environmentFile().'.'.$getoptResult['env']
			)) {
				return;
			}
		}

		$environment = Env::get('APP_ENV');

		if (! $environment) {
			return;
		}

		$this->setEnvironmentFilePath(
			$app, $app->environmentFile().'.'.$environment
		);
	}

	/**
	 * Load a custom environment file.
	 *
	 * @param  \Jeht\Ground\Interfaces\Application  $app
	 * @param  string  $file
	 * @return bool
	 */
	protected function setEnvironmentFilePath($app, $file)
	{
		if (is_file($app->environmentPath().'/'.$file)) {
			$app->loadEnvironmentFrom($file);

			return true;
		}

		return false;
	}

	/**
	 * Initializes the Env helper class.
	 *
	 * @param  \Jeht\Ground\Interfaces\Application  $app
	 * @return void
	 */
	protected function createEnv($app)
	{
		Env::terminate();
		//
		Env::initialize($app->environmentFile());
	}

	/**
	 * Write the error information to the screen and exit.
	 *
	 * @param  \Jeht\Exceptions\Filesystem\FileNotFoundException  $e
	 * @return void
	 */
	protected function writeErrorAndDie(FileNotFoundException $e)
	{
		if ($app->runningInConsole()) {
			echo "\r\n[ERROR] The environment file is invalid!";
			echo "\r\n" . $e->getMessage();

			exit(1);
		} else {
			throw $e;
		}
	}
}


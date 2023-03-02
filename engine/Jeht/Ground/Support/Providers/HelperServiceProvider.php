<?php
namespace Jeht\Ground\Support\Providers;

use Closure;
use Jeht\Support\HelperLoader;
use Jeht\Support\ServiceProvider;

class HelperServiceProvider extends ServiceProvider
{
	/**
	 * The Jeht platform helper files.
	 *
	 * @var array
	 */
	protected $primordial = [
		'engine/Jeht/Support/helpers.php',
		'engine/Jeht/Ground/helpers.php',
	];

	/**
	 * The helper folders.
	 *
	 * @var array
	 */
	protected $folders = [];

	/**
	 * The helper files to load.
	 *
	 * @var array
	 */
	protected $files = [];

	/**
	 * Register any application services.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->initializeLoader();
		$this->preparePrimordials();
		$this->prepareHelpers();
		$this->prepareHelperFolders();
		//
		foreach ($this->primordial as $helper) {
			$this->loader->addHelper($helper);
		}
		//
		foreach ($this->folders as $helperFolder) {
			$this->loader->addHelpers($helperFolder);
		}
		//
		foreach ($this->files as $helper) {
			$this->loader->addHelper($helper);
		}
		//
		$this->loader->require();
	}

	/**
	 * Bootstrap any application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		//
	}

	/**
	 * Register the callback that will be used to load the application's routes.
	 *
	 * @param  \Closure  $routesCallback
	 * @return $this
	 */
	protected function initializeLoader()
	{
		$this->app->singleton('helper.loader', function(){
			return new HelperLoader();
		});
		//
		$this->loader = $this->app['helper.loader'];
	}

	/**
	 * Set the root controller namespace for the application.
	 *
	 * @return void
	 */
	protected function preparePrimordials()
	{
		$helpers = $this->primordial;
		//
		$this->primordial = [];
		//
		foreach ($helpers as $helper) {
			$helper = realpath($this->app->kernelPath($helper));
			//
			if (is_file($helper) && is_readable($helper)) {
				$this->primordial[] = $helper;
			}
		}
	}

	/**
	 * Set the root controller namespace for the application.
	 *
	 * @return void
	 */
	protected function prepareHelpers()
	{
		$helpers = $this->files;
		//
		$this->files = [];
		//
		foreach ($helpers as $h => $helper) {
			$helper = realpath($this->app->basePath($helper));
			//
			if (is_file($helper) && is_readable($helper)) {
				$this->files[$h] = $helper;
				continue;
			}
			//
			$helper = realpath($this->app->kernelPath($helper));
			//
			if (is_file($helper) && is_readable($helper)) {
				$this->files[$h] = $helper;
				continue;
			}
		}
	}

	/**
	 * Verify if the listed helper folders exist and are readable.
	 *
	 * @return void
	 */
	protected function prepareHelperFolders()
	{
		$folders = $this->folders;
		//
		$this->folders = [];
		//
		foreach ($folders as $h => $folder) {
			$folder = realpath($this->app->basePath($folder));
			//
			if (is_dir($folder) && is_readable($folder)) {
				$this->folders[$h] = $folder;
				continue;
			}
			//
			$folder = realpath($this->app->kernelPath($folder));
			//
			if (is_dir($folder) && is_readable($folder)) {
				$this->folders[$h] = $folder;
				continue;
			}
		}
	}

}


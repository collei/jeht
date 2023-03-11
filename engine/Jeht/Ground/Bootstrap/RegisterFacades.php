<?php
namespace Jeht\Ground\Bootstrap;

use Jeht\Ground\Interfaces\Application;
use Jeht\Ground\Loaders\AliasLoader;
use Jeht\Ground\PackageManifest;
use Jeht\Support\Facades\Facade;

/**
 * Adapted from Laravel's Illuminate\Foundation\Bootstrap\RegisterFacades
 * @link https://laravel.com/api/8.x/Illuminate/Foundation/Bootstrap/RegisterFacades.html
 * @link https://github.com/laravel/framework/blob/8.x/src/Illuminate/Foundation/Bootstrap/RegisterFacades.php
 *
 */
class RegisterFacades
{
	/**
	 * Bootstrap the given application.
	 *
	 * @param  \Jeht\Ground\Interfaces\Application  $app
	 * @return void
	 */
	public function bootstrap(Application $app)
	{
		Facade::clearResolvedInstances();

		Facade::setFacadeApplication($app);

		AliasLoader::getInstance(array_merge(
			$app->make('config')->get('app.aliases', []),
			$app->make(PackageManifest::class)->aliases()
		))->register();
	}
}


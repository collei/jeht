<?php
namespace Jeht\Ground\Bootstrap;

use Jeht\Ground\Interfaces\Application;
use Jeht\Http\Request;

/**
 * Adapted from Laravel's Illuminate\Foundation\Bootstrap\SetRequestForConsole
 * @link https://laravel.com/api/8.x/Illuminate/Foundation/Bootstrap/SetRequestForConsole.html
 * @link https://github.com/laravel/framework/blob/8.x/src/Illuminate/Foundation/Bootstrap/SetRequestForConsole.php
 *
 */
class SetRequestForConsole
{
	/**
	 * Bootstrap the given application.
	 *
	 * @param  \Jeht\Ground\Interfaces\Application  $app
	 * @return void
	 */
	public function bootstrap(Application $app)
	{
		$uri = $app->make('config')->get('app.url', 'http://localhost');
		//
		$components = parse_url($uri);
		//
		$server = $_SERVER;
		//
		if (isset($components['path'])) {
			$server = array_merge($server, [
				'SCRIPT_FILENAME' => $components['path'],
				'SCRIPT_NAME' => $components['path'],
			]);
		}
		//
		$app->instance('request', Request::create(
			$uri, 'GET', [], [], [], $server
		));
	}
}


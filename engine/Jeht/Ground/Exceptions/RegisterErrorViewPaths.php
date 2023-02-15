<?php
namespace Jeht\Ground\Exceptions;

use Jeht\Support\Facades\View;

/**
 * From Laravel's Illuminate\Foundation\Exceptions\RegisterErrorViewPaths
 * @link https://laravel.com/api/8.x/Illuminate/Foundation/Exceptions/RegisterErrorViewPaths.html
 * @link https://github.com/laravel/framework/blob/8.x/src/Illuminate/Foundation/Exceptions/RegisterErrorViewPaths.php
 *
 */
class RegisterErrorViewPaths
{
	/**
	 * Register the error view paths.
	 *
	 * @return void
	 */
	public function __invoke()
	{
		View::replaceNamespace('errors', collect(config('view.paths'))->map(function ($path) {
			return "{$path}/errors";
		})->push(__DIR__.'/views')->all());
	}
}


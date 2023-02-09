<?php
namespace Jeht\Interfaces\Http;

/**
 * Interface of http kernel engine
 *
 * Adapted from Laravel's Illuminate\Contracts\Http\Kernel
 * @link https://laravel.com/api/8.x/Illuminate/Contracts/Http/Kernel.html
 * @link https://github.com/laravel/framework/blob/8.x/src/Illuminate/Contracts/Http/Kernel.php
 */
interface Kernel
{
	/**
	 * Bootstrap the application for HTTP requests.
	 *
	 * @return void
	 */
	public function bootstrap();

	/**
	 * Handle an incoming HTTP request.
	 *
	 * @param	\Jeht\Interfaces\Http\Request	$request
	 * @return	\Jeht\Interfaces\Http\Response
	 */
	public function handle($request);

	/**
	 * Perform any final actions for the request lifecycle.
	 *
	 * @param	\Jeht\Interfaces\Http\Request	$request
	 * @return	\Jeht\Interfaces\Http\Response	$response
	 * @return	void
	 */
	public function terminate($request, $response);

	/**
	 * Get the Laravel application instance.
	 *
	 * @return	\Jeht\Interfaces\Ground\Application
	 */
	public function getApplication();
}


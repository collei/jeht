<?php
namespace Jeht\Debug\Interfaces;

use Throwable;

/**
 * Adapted from Laravel's Illuminate\Contracts\Debug\ExceptionHandler
 * @link https://laravel.com/api/8.x/Illuminate/Contracts/Debug/ExceptionHandler.html
 * @link https://github.com/laravel/framework/blob/8.x/src/Illuminate/Contracts/Debug/ExceptionHandler.php
 */
interface ExceptionHandler
{
	/**
	 * Report or log an exception.
	 *
	 * @param  \Throwable  $e
	 * @return void
	 *
	 * @throws \Throwable
	 */
	public function report(Throwable $e);

	/**
	 * Determine if the exception should be reported.
	 *
	 * @param  \Throwable  $e
	 * @return bool
	 */
	public function shouldReport(Throwable $e);

	/**
	 * Render an exception into an HTTP response.
	 *
	 * @param  \Jeht\Http\Request  $request
	 * @param  \Throwable  $e
	 * @return \Jeht\Http\Response
	 * @throws \Throwable
	 */
	public function render($request, Throwable $e);

	/**
	 * Render an exception to the console.
	 *
	 * @param  \Symfony\Component\Console\Output\OutputInterface  $output
	 * @param  \Throwable  $e
	 * @return void
	 */
	public function renderForConsole($output, Throwable $e);
}


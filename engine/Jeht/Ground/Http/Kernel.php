<?php
namespace Jeht\Ground\Http;

use Jeht\Interfaces\Http\Kernel as KernelInterface;
use Jeht\Ground\Application;
use Jeht\Routing\Router;
use Jeht\Support\Facades\Facade;
use Jeht\Routing\Pipeline;

class Kernel implements KernelInterface
{
	/**
	 * @var \Jeht\Interfaces\Ground\Application
	 */
	protected $app;

	/**
	 * @var \Jeht\Routing\Router
	 */
	protected $router;

	/**
	 * Initializes the HTTP kernel.
	 *
	 * @param	\Jeht\Interfaces\Ground\Application	$app
	 */
	public function __construct(Application $app, Router $router)
	{
		$this->app = $app;
		$this->router = $router;
	}

	/**
	 * Bootstrap the application for HTTP requests.
	 *
	 * @return void
	 */
	public function bootstrap()
	{
		//
	}

	/**
	 * Handle an incoming HTTP request.
	 *
	 * @param	\Jeht\Interfaces\Http\Request	$request
	 * @return	\Jeht\Interfaces\Http\Response
	 */
	public function handle($request)
	{
		try {
			$response = $this->sendRequestThroughRouter($request);
		} catch (Throwable $the) {
			$this->reportException($the);
			//
			$response = $this->renderException($request, $the);
		}
		//
		return $response;
	}

	protected function sendRequestThroughRouter($request)
	{
		$this->app->instance('request', $request);
		//
		Facade::clearResolvedInstance('request');
		//
		$this->bootstrap();

		echo '<div>'.__METHOD__.':'.__LINE__.'</div>';
		//
		return (new Pipeline($this->app))
			->send($request)
			->through([])
			->then($this->dispatchToRouter());
	}

	protected function dispatchToRouter()
	{
		return function ($request) {
			$this->app->instance('request', $request);
			//
			return $this->router->dispatch($request);
		};
	}


	/**
	 * Perform any final actions for the request lifecycle.
	 *
	 * @param	\Jeht\Interfaces\Http\Request	$request
	 * @return	\Jeht\Interfaces\Http\Response	$response
	 * @return	void
	 */
	public function terminate($request, $response)
	{
		echo $response;
	}

	/**
	 * Get the application instance.
	 *
	 * @return	\Jeht\Interfaces\Ground\Application
	 */
	public function getApplication()
	{
		return $this->app;
	}


}


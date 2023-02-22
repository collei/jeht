<?php
namespace Jeht\Ground\Http;

use Jeht\Interfaces\Debug\ExceptionHandler;
use Jeht\Interfaces\Ground\Application;
use Jeht\Interfaces\Http\Kernel as KernelContract;
use Jeht\Ground\Http\Events\RequestHandled;
use Jeht\Routing\Pipeline;
use Jeht\Routing\Router;
use Jeht\Support\Facades\Facade;
use InvalidArgumentException;
use Throwable;
use Closure;

class Kernel implements KernelContract
{
	/**
	 * The application implementation.
	 *
	 * @var \Jeht\Interfaces\Ground\Application
	 */
	protected $app;

	/**
	 * The router instance.
	 *
	 * @var \Jeht\Routing\Router
	 */
	protected $router;

	/**
	 * The bootstrap classes for the application.
	 *
	 * @var string[]
	 */
	protected $bootstrappers = [
		\Jeht\Ground\Bootstrap\LoadEnvironmentVariables::class,
		\Jeht\Ground\Bootstrap\LoadConfiguration::class,
//		\Jeht\Ground\Bootstrap\HandleExceptions::class,
		\Jeht\Ground\Bootstrap\RegisterFacades::class,
		\Jeht\Ground\Bootstrap\RegisterProviders::class,
		\Jeht\Ground\Bootstrap\BootProviders::class,
	];

	/**
	 * The application's middleware stack.
	 *
	 * @var array
	 */
	protected $middleware = [];

	/**
	 * The application's route middleware groups.
	 *
	 * @var array
	 */
	protected $middlewareGroups = [];

	/**
	 * The application's route middleware.
	 *
	 * @var array
	 */
	protected $routeMiddleware = [];

	/**
	 * The priority-sorted list of middleware.
	 *
	 * Forces non-global middleware to always be in the given order.
	 *
	 * @var string[]
	 */
	protected $middlewarePriority = [
		\Jeht\Cookie\Middleware\EncryptCookies::class,
		\Jeht\Session\Middleware\StartSession::class,
		\Jeht\View\Middleware\ShareErrorsFromSession::class,
		\Jeht\Contracts\Auth\Middleware\AuthenticatesRequests::class,
		\Jeht\Routing\Middleware\ThrottleRequests::class,
		\Jeht\Routing\Middleware\ThrottleRequestsWithRedis::class,
		\Jeht\Session\Middleware\AuthenticateSession::class,
		\Jeht\Routing\Middleware\SubstituteBindings::class,
		\Jeht\Auth\Middleware\Authorize::class,
	];

	/**
	 * Create a new HTTP kernel instance.
	 *
	 * @param  \Jeht\Interfaces\Ground\Application  $app
	 * @param  \Jeht\Routing\Router  $router
	 * @return void
	 */
	public function __construct(Application $app, Router $router)
	{
		$this->app = $app;
		$this->router = $router;

		$this->syncMiddlewareToRouter();
	}

	/**
	 * Handle an incoming HTTP request.
	 *
	 * @param  \Jeht\Http\Request  $request
	 * @return \Jeht\Http\Response
	 */
	public function handle($request)
	{
		try {
			//$request->enableHttpMethodParameterOverride();
			$response = $this->sendRequestThroughRouter($request);
		} catch (Throwable $e) {
			$this->reportException($e);

			$response = $this->renderException($request, $e);
		}

		//$this->app['events']->dispatch(
		//	new RequestHandled($request, $response)
		//);

		return $response;
	}

	/**
	 * Send the given request through the middleware / router.
	 *
	 * @param  \Jeht\Http\Request  $request
	 * @return \Jeht\Http\Response
	 */
	protected function sendRequestThroughRouter($request)
	{
		$this->app->instance('request', $request);

		Facade::clearResolvedInstance('request');

		$this->bootstrap();

		return (new Pipeline($this->app))
					->send($request)
					->through($this->app->shouldSkipMiddleware() ? [] : $this->middleware)
					->then($this->dispatchToRouter());
	}

	/**
	 * Bootstrap the application for HTTP requests.
	 *
	 * @return void
	 */
	public function bootstrap()
	{
		if (! $this->app->hasBeenBootstrapped()) {
			$this->app->bootstrapWith($this->bootstrappers());
		}
	}

	/**
	 * Get the route dispatcher callback.
	 *
	 * @return \Closure
	 */
	protected function dispatchToRouter()
	{
		return function ($request) {
			$this->app->instance('request', $request);

			return $this->router->dispatch($request);
		};
	}

	/**
	 * Call the terminate method on any terminable middleware.
	 *
	 * @param  \Jeht\Http\Request  $request
	 * @param  \Jeht\Http\Response  $response
	 * @return void
	 */
	public function terminate($request, $response)
	{
		$this->terminateMiddleware($request, $response);

		$this->app->terminate();
	}

	/**
	 * Call the terminate method on any terminable middleware.
	 *
	 * @param  \Jeht\Http\Request  $request
	 * @param  \Jeht\Http\Response  $response
	 * @return void
	 */
	protected function terminateMiddleware($request, $response)
	{
		$middlewares = $this->app->shouldSkipMiddleware() ? [] : array_merge(
			$this->gatherRouteMiddleware($request),
			$this->middleware
		);

		foreach ($middlewares as $middleware) {
			if (! is_string($middleware)) {
				continue;
			}

			[$name] = $this->parseMiddleware($middleware);

			$instance = $this->app->make($name);

			if (method_exists($instance, 'terminate')) {
				$instance->terminate($request, $response);
			}
		}
	}

	/**
	 * Gather the route middleware for the given request.
	 *
	 * @param  \Jeht\Http\Request  $request
	 * @return array
	 */
	protected function gatherRouteMiddleware($request)
	{
		if ($route = $request->route()) {
			return $this->router->gatherRouteMiddleware($route);
		}

		return [];
	}

	/**
	 * Parse a middleware string to get the name and parameters.
	 *
	 * @param  string  $middleware
	 * @return array
	 */
	protected function parseMiddleware($middleware)
	{
		[$name, $parameters] = array_pad(explode(':', $middleware, 2), 2, []);

		if (is_string($parameters)) {
			$parameters = explode(',', $parameters);
		}

		return [$name, $parameters];
	}

	/**
	 * Determine if the kernel has a given middleware.
	 *
	 * @param  string  $middleware
	 * @return bool
	 */
	public function hasMiddleware($middleware)
	{
		return in_array($middleware, $this->middleware);
	}

	/**
	 * Add a new middleware to the beginning of the stack if it does not already exist.
	 *
	 * @param  string  $middleware
	 * @return $this
	 */
	public function prependMiddleware($middleware)
	{
		if (array_search($middleware, $this->middleware) === false) {
			array_unshift($this->middleware, $middleware);
		}

		return $this;
	}

	/**
	 * Add a new middleware to end of the stack if it does not already exist.
	 *
	 * @param  string  $middleware
	 * @return $this
	 */
	public function pushMiddleware($middleware)
	{
		if (array_search($middleware, $this->middleware) === false) {
			$this->middleware[] = $middleware;
		}

		return $this;
	}

	/**
	 * Prepend the given middleware to the given middleware group.
	 *
	 * @param  string  $group
	 * @param  string  $middleware
	 * @return $this
	 *
	 * @throws \InvalidArgumentException
	 */
	public function prependMiddlewareToGroup($group, $middleware)
	{
		if (! isset($this->middlewareGroups[$group])) {
			throw new InvalidArgumentException("The [{$group}] middleware group has not been defined.");
		}

		if (array_search($middleware, $this->middlewareGroups[$group]) === false) {
			array_unshift($this->middlewareGroups[$group], $middleware);
		}

		$this->syncMiddlewareToRouter();

		return $this;
	}

	/**
	 * Append the given middleware to the given middleware group.
	 *
	 * @param  string  $group
	 * @param  string  $middleware
	 * @return $this
	 *
	 * @throws \InvalidArgumentException
	 */
	public function appendMiddlewareToGroup($group, $middleware)
	{
		if (! isset($this->middlewareGroups[$group])) {
			throw new InvalidArgumentException("The [{$group}] middleware group has not been defined.");
		}

		if (array_search($middleware, $this->middlewareGroups[$group]) === false) {
			$this->middlewareGroups[$group][] = $middleware;
		}

		$this->syncMiddlewareToRouter();

		return $this;
	}

	/**
	 * Prepend the given middleware to the middleware priority list.
	 *
	 * @param  string  $middleware
	 * @return $this
	 */
	public function prependToMiddlewarePriority($middleware)
	{
		if (! in_array($middleware, $this->middlewarePriority)) {
			array_unshift($this->middlewarePriority, $middleware);
		}

		$this->syncMiddlewareToRouter();

		return $this;
	}

	/**
	 * Append the given middleware to the middleware priority list.
	 *
	 * @param  string  $middleware
	 * @return $this
	 */
	public function appendToMiddlewarePriority($middleware)
	{
		if (! in_array($middleware, $this->middlewarePriority)) {
			$this->middlewarePriority[] = $middleware;
		}

		$this->syncMiddlewareToRouter();

		return $this;
	}

	/**
	 * Sync the current state of the middleware to the router.
	 *
	 * @return void
	 */
	protected function syncMiddlewareToRouter()
	{
		$this->router->middlewarePriority = $this->middlewarePriority;

		foreach ($this->middlewareGroups as $key => $middleware) {
			$this->router->middlewareGroup($key, $middleware);
		}

		foreach ($this->routeMiddleware as $key => $middleware) {
			$this->router->aliasMiddleware($key, $middleware);
		}
	}

	/**
	 * Get the priority-sorted list of middleware.
	 *
	 * @return array
	 */
	public function getMiddlewarePriority()
	{
		return $this->middlewarePriority;
	}

	/**
	 * Get the bootstrap classes for the application.
	 *
	 * @return array
	 */
	protected function bootstrappers()
	{
		return $this->bootstrappers;
	}

	/**
	 * Report the exception to the exception handler.
	 *
	 * @param  \Throwable  $e
	 * @return void
	 */
	protected function reportException(Throwable $e)
	{
		throw $e;
		//$this->app[ExceptionHandler::class]->report($e);
	}

	/**
	 * Render the exception to a response.
	 *
	 * @param  \Jeht\Http\Request  $request
	 * @param  \Throwable  $e
	 * @return \Jeht\Http\Response
	 */
	protected function renderException($request, Throwable $e)
	{
		return ''; //$this->app[ExceptionHandler::class]->render($request, $e);
	}

	/**
	 * Get the application's route middleware groups.
	 *
	 * @return array
	 */
	public function getMiddlewareGroups()
	{
		return $this->middlewareGroups;
	}

	/**
	 * Get the application's route middleware.
	 *
	 * @return array
	 */
	public function getRouteMiddleware()
	{
		return $this->routeMiddleware;
	}

	/**
	 * Get the Laravel application instance.
	 *
	 * @return \Jeht\Interfaces\Ground\Application
	 */
	public function getApplication()
	{
		return $this->app;
	}

	/**
	 * Set the Laravel application instance.
	 *
	 * @param  \Jeht\Interfaces\Ground\Application  $app
	 * @return $this
	 */
	public function setApplication(Application $app)
	{
		$this->app = $app;

		return $this;
	}

}


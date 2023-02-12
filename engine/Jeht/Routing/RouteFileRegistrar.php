<?php
namespace Jeht\Routing;

/**
 * Adapted from Laravel's \Illuminate\Routing\RouteFileRegistrar
 * @link https://laravel.com/api/8.x/Illuminate/Routing/RouteFileRegistrar.html
 * @link https://github.com/laravel/framework/blob/8.x/src/Illuminate/Routing/RouteFileRegistrar.php
 *
 */
class RouteFileRegistrar
{
	/**
	 * The router instance.
	 *
	 * @var \Jeht\Routing\Router
	 */
	protected $router;

	/**
	 * Create a new route file registrar instance.
	 *
	 * @param  \Jeht\Routing\Router  $router
	 * @return void
	 */
	public function __construct(Router $router)
	{
		$this->router = $router;
	}

	/**
	 * Require the given routes file.
	 *
	 * @param  string  $routes
	 * @return void
	 */
	public function register($routes)
	{
		$router = $this->router;
		//
		require $routes;
	}
}


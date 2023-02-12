<?php
namespace Jeht\Routing;

use Jeht\Support\Str;
use Jeht\Ground\Application;

class RouteGroup
{
	/**
	 * @var array
	 */
	private const CATEGORIES = [
		'name' => 'names',
		'prefix' => 'prefixes',
		'controller' => 'controllers',
		'namespace' => 'namespaces',
	];

	/**
	 * @var \Jeht\Ground\Application
	 */
	private $app;

	/**
	 * @var \Jeht\Routing\Router
	 */
	private $router;

	/**
	 * @var array
	 */
	private $names = [];

	/**
	 * @var array
	 */
	private $prefixes = [];

	/**
	 * @var array
	 */
	private $controllers = [];

	/**
	 * @var array
	 */
	private $namespaces = [];

	/**
	 * @var array
	 */
	private $current = [
		'name' => null,
		'prefix' => null,
		'controller' => null,
		'namespace' => null,
	];

	/**
	 * @var array
	 */
	private $currentLevel = -1;

	/**
	 * Accumulates parameter levels for a group callback.
	 *
	 * @return void
	 */
	private function beginGroup()
	{
		++$this->currentLevel;
		//
		foreach (self::CATEGORIES as $singular => $plural) {
			$this->$plural[] = $this->current[$singular];
		}
	}

	/**
	 * De-accumulates parameter levels after a group callback.
	 *
	 * @return void
	 */
	private function endGroup()
	{
		foreach (self::CATEGORIES as $singular => $plural) {
			array_pop($this->$plural);
		}
		//
		--$this->currentLevel;
	}

	/**
	 * Initializes the route grouper
	 *
	 * @param	\Jeht\Ground\Application	$app
	 */
	public function __construct(Application $app)
	{
		$this->app = $app;
	}

	/**
	 * Sets the route to work with
	 *
	 * @param \Jeht\Routing\Router $router
	 * @return self
	 */
	public function setRouter(Router $router)
	{
		$this->router = $router;
		return $this;
	}

	/**
	 * Adds a name segment for the next group calls
	 *
	 * @param string $name
	 * @return self
	 */
	public function name(string $name)
	{
		$this->current['name'] = $name;
		return $this;
	}

	/**
	 * Adds a path segment for the next group calls
	 *
	 * @param string $prefix
	 * @return self
	 */
	public function prefix(string $prefix)
	{
		$this->current['prefix'] = $prefix;
		return $this;
	}

	/**
	 * Sets the controller for the next group calls
	 *
	 * @param string $controller
	 * @return self
	 */
	public function controller(string $controller)
	{
		$this->current['controller'] = $controller;
		return $this;
	}

	/**
	 * Sets the namespace replacement for the current controller
	 * for the next group calls
	 *
	 * @param string $namespace
	 * @return self
	 */
	public function namespace(string $namespace)
	{
		$this->current['namespace'] = $namespace;
		return $this;
	}

	/**
	 * Calls the given $callback into the current group context
	 *
	 * @param \Closure $callaback
	 * @return void
	 */
	public function group($callback)
	{
		$this->beginGroup();
		//
		$callback();
		//
		$this->endGroup();
		//
		$this->tellRouterToFetchRoutes();
	}

	/**
	 * Tells the route registrar to fetch any pending routes
	 * and to register them. 
	 *
	 * @return void
	 */
	protected function tellRouterToFetchRoutes()
	{
		if ($this->router) {
			$this->router->registerRoutes();
		}
	}

	/**
	 * Returns the currently accumulated name
	 *
	 * @param string|null $separator
	 * @return string
	 */
	public function getCurrentName(string $separator = null)
	{
		$qualified = implode(($separator ?? ''), $this->names);
		//
		if ($separator) {
			$qualified = str_replace(
				[$separator.$separator.$separator, $separator.$separator],
				[$separator, $separator],
				$qualified
			);
		}
		//
		return $qualified;
	}

	/**
	 * Returns the currently accumulated path
	 *
	 * @return string
	 */
	public function getCurrentPrefix()
	{
		return str_replace('//', '/', implode('/', $this->prefixes));
	}

	/**
	 * Returns the current controller
	 *
	 * @return string
	 */
	public function getCurrentController()
	{
		if ($this->currentLevel >= 0) {
			return $this->controllers[$this->currentLevel] ?? null;
		}
		//
		return null;
	}

	/**
	 * Returns the current controller namespace
	 *
	 * @return string
	 */
	public function getCurrentNamespace()
	{
		if ($this->currentLevel >= 0) {
			return $this->namespaces[$this->currentLevel] ?? null;
		}
		//
		return null;
	}

	/**
	 * Return the current group parameters as an associative array
	 *
	 * @return array
	 */
	public function getCurrent()
	{
		return array(
			'name' => $this->getCurrentName('.'),
			'prefix' => $this->getCurrentPrefix(),
			'controller' => $this->getCurrentController(),
			'namespace' => $this->getCurrentNamespace()
		);
	}

}


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
		'action' => 'actions',
		'namespace' => 'namespaces',
		'middleware' => 'middlewares',
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
	private $campi = [];

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
	private $actions = [];

	/**
	 * @var array
	 */
	private $namespaces = [];

	/**
	 * @var array
	 */
	private $middlewares = [];

	/**
	 * @var array
	 */
	private $current = [
		'name' => null,
		'prefix' => null,
		'action' => null,
		'namespace' => null,
		'middleware' => null,
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
			$this->{$plural}[] = $this->current[$singular];
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
	 * Sets the action for the next group calls
	 *
	 * @param string $action
	 * @return self
	 */
	public function action(string $action)
	{
		$this->current['action'] = $action;
		return $this;
	}

	/**
	 * Sets the namespace replacement for the current action
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
	 * Sets the middleware for the next group calls
	 *
	 * @param string $middleware
	 * @return self
	 */
	public function middleware(string $middleware)
	{
		$this->current['middleware'] = $middleware;
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
		$separator = substr(trim($separator ?? ''), 0, 1);
		//
		$qualified = implode(($separator ?? ''), $this->names);
		//
		if ($separator) {
			$qualified = str_replace(
				[$separator.$separator.$separator, $separator.$separator],
				$separator,
				$qualified
			);
		}
		//
		return trim($qualified, $separator);
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
	 * Returns the current action
	 *
	 * @return string
	 */
	public function getCurrentAction()
	{
		if ($this->currentLevel >= 0) {
			return $this->actions[$this->currentLevel] ?? null;
		}
		//
		return null;
	}

	/**
	 * Returns the current action namespace
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
			'action' => $this->getCurrentAction(),
			'namespace' => $this->getCurrentNamespace()
		);
	}

}


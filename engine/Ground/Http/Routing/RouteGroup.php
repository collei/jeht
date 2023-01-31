<?php
namespace Ground\Http\Routing;

class RouteGroup
{
	private const CATEGORIES = [
		'name' => 'names',
		'prefix' => 'prefixes',
		'controller' => 'controllers',
		'namespace' => 'namespaces',
	];

	private $names = [];
	private $prefixes = [];
	private $controllers = [];
	private $namespaces = [];

	private $current = [
		'name' => null,
		'prefix' => null,
		'controller' => null,
		'namespace' => null,
	];

	private $currentLevel = -1;

	private function beginGroup()
	{
		++$this->currentLevel;
		//
		foreach (self::CATEGORIES as $singular => $plural) {
			$this->$plural[] = $this->current[$singular];
		}
	}

	private function endGroup()
	{
		foreach (self::CATEGORIES as $singular => $plural) {
			array_pop($this->$plural);
		}
		//
		--$this->currentLevel;
	}

	public function __construct()
	{
		//
	}

	public function name(string $name)
	{
		$this->current['name'] = $name;
		return $this;
	}

	public function prefix(string $prefix)
	{
		$this->current['prefix'] = $prefix;
		return $this;
	}

	public function controller(string $controller)
	{
		$this->current['controller'] = $controller;
		return $this;
	}

	public function namespace(string $namespace)
	{
		$this->current['namespace'] = $namespace;
		return $this;
	}

	public function group($callback)
	{
		$this->beginGroup();
		$callback();
		$this->endGroup();
	}

	public function getCurrentName(stirng $separator = null)
	{
		return implode(($separator ?? ''), $this->names);
	}

	public function getCurrentPrefix()
	{
		return str_replace('//', '/', implode('/', $this->prefixes));
	}

	public function getCurrentController()
	{
		if ($this->currentLevel >= 0) {
			return $this->controllers[$this->currentLevel] ?? null;
		}
		//
		return null;
	}

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
			'name' => $this->getCurrentName(),
			'prefix' => $this->getCurrentPrefix(),
			'controller' => $this->getCurrentController(),
			'namespace' => $this->getCurrentNamespace()
		);
	}

}


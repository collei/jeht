<?php
namespace Jeht\Routing;

use Jeht\Interfaces\Container\Container;
use Jeht\Ground\Application;

class RouteCacheGenerator
{
	/**
	 * @var \Jeht\Routing\Router
	 */
	protected $router;

	/**
	 * @var \Jeht\Ground\Application
	 */
	protected $container;

	/**
	 * @var \Jeht\Routing\Router
	 */
	protected $collection;

	/**
	 * Creates a new instance of the cache creator agent
	 *
	 * @param \Jeht\Routing\Router $router
	 * @param \Jeht\Ground\Application $container
	 * @return void
	 */
	public function __construct(Router $router, Application $container)
	{
		$this->router = $router;
		$this->container = $container;
	}

	/**
	 * Defines the RouteCollection to work with
	 *
	 * @param \Jeht\Routing\AbstractRouteCollection $collection
	 * @return $this
	 */
	public function setCollection(AbstractRouteCollection $collection)
	{
		$this->collection = $collection;
		//
		return $this;
	}

	/**
	 * Creates the cache file
	 *
	 * @return $this
	 */
	public function cacheIfNeeded(bool $force = false)
	{
		if ($this->container->routesAreCached() && !$force) {
			return $this;
		}
		//
		return $this->cache();
	}

	/**
	 * Creates the cache file
	 *
	 * @return $this
	 */
	public function cache()
	{
		if ($stub = $this->getRoutesStubPath()) {
			$cached = $this->container->getCachedRoutesPath();
			$encoded = base64_encode(serialize($this->getRouteCollectionContents()));

			$content = str_replace(
				'{{routes}}', $encoded, file_get_contents($stub)
			);

			file_put_contents($cached, $content);
		}
		//
		return $this;
	}

	/**
	 * Returns the route cache stub file, if any
	 *
	 * @return string|null
	 */
	protected function getRoutesStubPath()
	{
		if ($this->container) {
			return $this->container['path.storage'] . '/stubs/routes.stub';
		}
		//
		return null;
	}

	/**
	 * Returns the route collection contents as array ready for serialization.
	 *
	 * @return array
	 */
	protected function getRouteCollectionContents()
	{
		$collection = $this->collection;
		//
		if ($collection instanceof RouteCollection) {
			$collection = $collection->toCompiledRouteCollection(
				$this->router, $this->container
			);
		}
		//
		return $collection->__serialize();
	}

}



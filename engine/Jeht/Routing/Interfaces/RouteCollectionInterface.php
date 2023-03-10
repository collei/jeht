<?php
namespace Jeht\Routing\Interfaces;

use Jeht\Routing\Interfaces\RouteInterface;
use Jeht\Http\Request;

/**
 * Adapted from Jeht\Routing\RouteCollectionInterface
 *
 */
interface RouteCollectionInterface
{
	/**
	 * Add a RouteInterface instance to the collection.
	 *
	 * @param  \Jeht\Routing\Interfaces\RouteInterface  $route
	 * @return \Jeht\Routing\Interfaces\RouteInterface
	 */
	public function add(RouteInterface $route);

	/**
	 * Refresh the name look-up table.
	 *
	 * This is done in case any names are fluently defined or if routes are overwritten.
	 *
	 * @return void
	 */
	public function refreshNameLookups();

	/**
	 * Refresh the action look-up table.
	 *
	 * This is done in case any actions are overwritten with new controllers.
	 *
	 * @return void
	 */
	public function refreshActionLookups();

	/**
	 * Find the first route matching a given request.
	 *
	 * @param  \Jeht\Http\Request  $request
	 * @return \Jeht\Routing\Interfaces\RouteInterface
	 *
	 * @throws \Jeht\Exceptions\Http\MethodNotAllowedHttpException
	 * @throws \Jeht\Exceptions\Http\NotFoundHttpException
	 */
	public function match(Request $request);

	/**
	 * Get routes from the collection by method.
	 *
	 * @param  string|null  $method
	 * @return \Jeht\Routing\Route[]
	 */
	public function get($method = null);

	/**
	 * Determine if the route collection contains a given named route.
	 *
	 * @param  string  $name
	 * @return bool
	 */
	public function hasNamedRoute($name);

	/**
	 * Get a route instance by its name.
	 *
	 * @param  string  $name
	 * @return \Jeht\Routing\Interfaces\RouteInterface|null
	 */
	public function getByName($name);

	/**
	 * Get a route instance by its controller action.
	 *
	 * @param  string  $action
	 * @return \Jeht\Routing\Interfaces\RouteInterface|null
	 */
	public function getByAction($action);

	/**
	 * Get all of the routes in the collection.
	 *
	 * @return \Jeht\Routing\Interfaces\RouteInterface[]
	 */
	public function getRoutes();

	/**
	 * Get all of the routes keyed by their HTTP verb / method.
	 *
	 * @return array
	 */
	public function getRoutesByMethod();

	/**
	 * Get all of the routes keyed by their name.
	 *
	 * @return \Jeht\Routing\Interfaces\RouteInterface[]
	 */
	public function getRoutesByName();
}


<?php
namespace Jeht\Interfaces\Routing;

use Psr\Http\Message\UriInterface;
use Jeht\Interfaces\Http\Request;

interface RouteInterface
{
	/**
	 * Checks if the given $requestUri matches the route.
	 *
	 * @param \Jeht\Interfaces\Http\Request $request
	 * @param bool $includingMethod
	 * @return bool
	 */
	public function matches(Request $request, bool $includingMethod = true);

	/**
	 * Checks if the last call to matches() method has generated any parameters.
	 *
	 * @return bool
	 */
	public function hasParameters();

	/**
	 * Returns all parameters as an associative array
	 * since the last call to matches() method.
	 *
	 * @return array|null
	 */
	public function getParameters();

}



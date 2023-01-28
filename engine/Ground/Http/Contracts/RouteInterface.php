<?php
namespace Ground\Http\Contracts;

use Psr\Http\Message\UriInterface;

interface RouteInterface
{
	/**
	 * Checks if the given $requestUri matches the route.
	 *
	 * @param string|UriInterface $requestUri
	 * @return bool
	 */
	public function matches($requestUri);

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



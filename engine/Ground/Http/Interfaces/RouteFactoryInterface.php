<?php
namespace Ground\Http\Interfaces;

use Ground\Http\Interfaces\RouteInterface;

interface RouteFactoryInterface 
{
	/**
	 * Adds a regex constraint to the given $parameter.
	 * Setting the second parameter to null removes the restriction.
	 *
	 * @param string $parameter
	 * @param string $regex = null
	 * @return self
	 */
	public function where(string $parameter, string $regex = null);

	/**
	 * Adds an alpha regex constraint to the given $parameter.
	 *
	 * @param string $parameter
	 * @return self
	 */
	public function whereAlpha(string $parameter);

	/**
	 * Adds an numeric regex constraint to the given $parameter.
	 *
	 * @param string $parameter
	 * @return self
	 */
	public function whereNumber(string $parameter);

	/**
	 * Adds an alphanumeric regex constraint to the given $parameter.
	 *
	 * @param string $parameter
	 * @return self
	 */
	public function whereAlphaNumeric(string $parameter);

	/**
	 * Adds a lisgting regex constraint to the given $parameter.
	 *
	 * @param string $parameter
	 * @param array $values
	 * @return self
	 */
	public function whereIn(string $parameter, array $values);

	/**
	 * Generates and returns the resulting RouteInterface instance.
	 *
	 * @return RouteInterface
	 */
	public function fetch();

}



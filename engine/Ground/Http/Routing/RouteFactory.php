<?php
namespace Ground\Http\Routing;

use Ground\Http\Routing\Route;
use Ground\Http\Routing\Router;
use Ground\Http\Contracts\RouteFactoryInterface;


/**
 * Implements the RouteFactoryInterface
 *
 */
class RouteFactory implements RouteFactoryInterface 
{
	/**
	 * @var string
	 */
	protected const REGEX_ANY = '[^\\/]+';

	/**
	 * @var string
	 */
	protected const REGEX_ALPHA = '[A-Za-z_]+';

	/**
	 * @var string
	 */
	protected const REGEX_ALPHANUMERIC = '[A-Za-z_0-9]+';

	/**
	 * @var string
	 */
	protected const REGEX_NUMBER = '[0-9]+';

	/**
	 * @var array $verbs
	 */
	protected $verbs = [];

	/**
	 * @var array $parameters
	 */
	protected $parameters = [];

	/**
	 * @var string $path
	 */
	protected $path;

	/**
	 * Apply the given $regex restrictor to the given $parameter
	 *
	 * @param string $path
	 * @param string $regex = null
	 */
	protected function constrictParameterTo(
		string $parameter, string $regex = null
	) {
		$this->parameters[$parameter] = $regex ?? self::REGEX_ANY;
	}

	/**
	 * Starts a new RouteFactory instance
	 *
	 * @param string $path
	 * @param string ...$verbs
	 */
	public function __construct(string $path, string ...$verbs)
	{
		$this->path = $path;
		//
		if (empty($verbs)) {
			$this->verbs[] = 'GET'
		} else {
			foreach ($verbs as $verb) {
				$this->verbs[] = $verb;
			}
		}
	}

	/**
	 * Adds a regex constraint to the given $parameter.
	 * Setting the second parameter to null removes the restriction.
	 *
	 * @param string $parameter
	 * @param string $regex = null
	 * @return self
	 */
	public function where($parameter, string $regex = null)
	{
		if (is_array($parameter)) {
			foreach ($parameter as $param => $rgx) {
				$this->constrictParameterTo($param, $rgx);
			}
		} elseif (is_string($parameter)) {
			$this->constrictParameterTo($parameter, $rgx);
		}
		//
		return $this;
	}

	/**
	 * Adds an alpha regex constraint to the given $parameter.
	 *
	 * @param string $parameter
	 * @return self
	 */
	public function whereAlpha(string $parameter)
	{
		$this->constrictParameterTo($parameter, self::REGEX_ALPHA);
		return $this;
	}

	/**
	 * Adds an numeric regex constraint to the given $parameter.
	 *
	 * @param string $parameter
	 * @return self
	 */
	public function whereNumber(string $parameter)
	{
		$this->constrictParameterTo($parameter, self::REGEX_NUMBER);
		return $this;
	}

	/**
	 * Adds an alphanumeric regex constraint to the given $parameter.
	 *
	 * @param string $parameter
	 * @return self
	 */
	public function whereAlphaNumeric(string $parameter)
	{
		$this->constrictParameterTo($parameter, self::REGEX_ALPHANUMERIC);
		return $this;
	}

	/**
	 * Adds a lisgting regex constraint to the given $parameter.
	 *
	 * @param string $parameter
	 * @param array $values
	 * @return self
	 */
	public function whereIn(string $parameter, array $values)
	{
		$regex = '(' . implode('|', $values) . ')';
		$this->constrictParameterTo($parameter, $regex);
		return $this;
	}

	/**
	 * Generates and returns the resulting RouteInterface instance.
	 *
	 * @return RouteInterface
	 */
	public function fetch()
	{
		return new Route(
			$this->path,
			Router::pathToRegex($this->path, $this->parameters),
			$this->verbs
		);
	}

}



<?php
namespace Jeht\Routing;

use Jeht\Interfaces\Routing\RouteFactoryInterface;
use Jeht\Support\Str;

/**
 * Implements the RouteFactoryInterface
 *
 */
class RouteFactory implements RouteFactoryInterface 
{
	/**
	 * @var string
	 */
	protected const REGEX_IU_PARAM = '/\\{(\\w+)(\\?)?\\}/';

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
	 * @var string
	 */
	protected const REGEX_UUID = '[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}';

	/**
	 * @var string[]
	 */
	protected $httpMethods = [];

	/**
	 * @var array
	 */
	protected $parameters = [];

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $uri;

	/**
	 * @var mixed
	 */
	protected $action;

	/**
	 * Translates a route uri into a regex that may be used to collect
	 * named parameters easily.
	 *
	 * Use the second parameter to override the default regex piece for
	 * one or more parameters, so you can add custom constraints for, e.g.,
	 * alphanumeric. The default piece matches anything but forward slashes.
	 *
	 * @param string $uri
	 * @param array $paramRegex
	 * @return string|false
	 */
	protected static function compileRegex(string $uri, array $paramRegex = [])
	{
		if (preg_match_all(self::REGEX_IU_PARAM, $uri, $matches, PREG_SET_ORDER)) {
			$regex = $uri;
			//
			foreach ($matches as $match) {
				$regexp = self::REGEX_ANY;
				//
				// override a parameter regex piece if set
				if (array_key_exists($match[1], $paramRegex)) {
					$regexp = $paramRegex[$match[1]];
				}
				//
				$piece = '(?P<' . $match[1] . '>' . $regexp . ')';
				//
				// if parameter is defined as optional...
				if (isset($match[2])) {
					$piece .= '?';
				}
				//
				$regex = str_replace($match[0], $piece, $regex);
			}
			//
			return $regex;
		}
		//
		return false;
	}

	/**
	 * Apply the given $regex restrictor to the given $parameter
	 *
	 * @param string $uri
	 * @param string $regex = null
	 * @return self
	 */
	protected function constrictParameterTo(
		string $parameter, string $regex = null
	) {
		$this->parameters[$parameter] = $regex ?? self::REGEX_ANY;
		//
		return $this;
	}

	/**
	 * Starts a new RouteFactory instance
	 *
	 * @param string $uri
	 * @param array $httpMethods
	 * @param mixed $action
	 * @param string|null $name
	 */
	public function __construct(
		string $uri, array $httpMethods, $action, string $name = null
	) {
		$this->uri = $uri;
		$this->action = $action;
		$this->httpMethods = $httpMethods;
		$this->name = $name;
	}

	/**
	 * Adds a name to the route. In a group, suffixes it.
	 *
	 * @param string $parameter
	 * @return self
	 */
	public function name(string $name)
	{
		$name = trim($name, ' 	.');
		//
		$this->name = !empty($this->name)
			? (rtrim($this->name, '.').'.'.$name)
			: $name;
		//
		return $this;
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
			$this->constrictParameterTo($parameter, $regex);
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
		return $this->constrictParameterTo($parameter, self::REGEX_ALPHA);
	}

	/**
	 * Adds an numeric regex constraint to the given $parameter.
	 *
	 * @param string $parameter
	 * @return self
	 */
	public function whereNumber(string $parameter)
	{
		return $this->constrictParameterTo($parameter, self::REGEX_NUMBER);
	}

	/**
	 * Adds an alphanumeric regex constraint to the given $parameter.
	 *
	 * @param string $parameter
	 * @return self
	 */
	public function whereAlphaNumeric(string $parameter)
	{
		return $this->constrictParameterTo($parameter, self::REGEX_ALPHANUMERIC);
	}

	/**
	 * Adds an UUID regex constraint to the given $parameter.
	 *
	 * @param string $parameter
	 * @return self
	 */
	public function whereUuid(string $parameter)
	{
		return $this->constrictParameterTo($parameter, self::REGEX_UUID);
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
		//
		return $this->constrictParameterTo($parameter, $regex);
	}

	/**
	 * Generates and returns the resulting RouteInterface instance.
	 *
	 * @return \Jeht\Interfaces\Routing\RouteInterface
	 */
	public function fetch()
	{
		return new Route(
			$this->httpMethods,
			$this->uri,
			$this->action,
			self::compileRegex($this->uri, $this->parameters),
			$this->name ?? Str::randomize(32)
		);
	}

}


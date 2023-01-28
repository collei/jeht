<?php
namespace Ground\Http\Routing;

use Ground\Http\Routing\Router;

use Ground\Http\Contracts\RouteInterface;
use Psr\Http\Message\UriInterface;

/**
 * Represents a Route in the system.
 *
 */
class Route implements RouteInterface
{
	/**
	 * @var array $verbs
	 */
	private $verbs = [];

	/**
	 * @var string $path
	 */
	private $path;

	/**
	 * @var string $regex = NULL
	 */
	private $regex = NULL;

	/**
	 * @var array $parameters
	 */
	private $parameters = [];

	/**
	 * Builds a new Route
	 *
	 * @param string $path
	 * @param string $regex = null
	 * @param string ...$verbs
	 */
	public function __construct(string $path, string $regex = null, string ...$verbs)
	{
		$this->path = $path;
		$this->regex = $regex ?? str_replace('/', '\\/', $path);
		$this->verbs = !empty($verbs) ? $verbs : ['GET'];
	}

	/**
	 * Checks if the given $requestUri matches the route,
	 * setting parameters if any found. 
	 *
	 * @param string $requestUri
	 * @return bool
	 */
	protected function matchAndSetParameters(string $requestUri)
	{
		[$bool, $parameters] = Router::requestMatchesRegex(
			$requestUri, $this->regex
		);
		//
		if ($bool) {
			$this->parameters = !empty($parameters) ? $parameters : [];
		}
		//
		return $bool;
	}

	/**
	 * Checks if the given $requestUri matches the route.
	 *
	 * @param string|UriInterface $requestUri
	 * @return bool
	 */
	public function matches($requestUri)
	{
		// type checking / conformation
		if ($requestUri instanceof UriInterface) {
			$requestUri = $requestUri->getPath();
		} elseif (!is_string($requestUri)) {
			$message = 'parameter must be a string or an instanceof '
				. '\Psr\Http\Message\UriInterface'
			//
			throw new \InvalidArgumentException($message);
		}
		//
		return $this->matchAndSetParameters($requestUri);
	}

	/**
	 * Checks if the last call to matches() method has generated any parameters.
	 *
	 * @return bool
	 */
	public function hasParameters()
	{
		return !empty($this->parameters);
	}

	/**
	 * Returns all parameters as an associative array
	 * since the last call to matches() method.
	 *
	 * @return array|null
	 */
	public function getParameters()
	{
		if ($this->hasParameters()) {
			return $this->parameters;
		}
		//
		return null;
	}


}



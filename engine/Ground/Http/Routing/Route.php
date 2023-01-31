<?php
namespace Ground\Http\Routing;

use Ground\Http\Routing\Router;
use Ground\Support\Arr;
use Ground\Support\Str;
use InvalidArgumentException;

use Ground\Http\Contracts\RouteInterface;
use Psr\Http\Message\UriInterface;

/**
 * Represents a Route in the system.
 *
 */
class Route //implements RouteInterface
{
	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var string[]
	 */
	private $httpMethods;

	/**
	 * @var string
	 */
	private $path;

	/**
	 * @var string
	 */
	private $regex = null;

	/**
	 * @var mixed
	 */
	private $handler;

	/**
	 * @var array
	 */
	private $parameters = [];


	protected function setHttpMethods($httpMethods)
	{
		if (empty($httpMethods)) {
			$this->httpMethods = array('GET','HEAD');
			return;
		}
		//
		if (!is_array($httpMethods) && !is_string($httpMethods)) {
			throw new InvalidArgumentException('Parameter must be a string or array');
		}
		//
		$this->httpMethod = Arr::wrap($httpMethods);
	}

	/**
	 * Builds a new Route
	 *
	 * @param string|array $httpMethods
	 * @param string $path
	 * @param mixed $handler
	 * @param string|null $regex
	 * @param string|null $name
	 */
	public function __construct(
		$httpMethods,
		string $path,
		$handler,
		string $regex = null,
		string $name = null
	) {
		$this->name = $name ?? Str::randomize();
		$this->path = $path;
		$this->handler = $handler;
		$this->regex = $regex ?? str_replace('/', '\\/', $path);
		//
		$this->setHttpMethods($httpMethods);
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
	 * @param string $httpMethod
	 * @param string|UriInterface $requestUri
	 * @return bool
	 */
	public function matches(string $httpMethod, $requestUri)
	{
		if (($httpMethod !== $this->httpMethod) && ('*' !== $this->httpMethod)) {
			return false;
		}
		//
		// type checking / conformation
		if ($requestUri instanceof UriInterface) {
			$requestUri = $requestUri->getPath();
		} elseif (!is_string($requestUri)) {
			$message = 'parameter must be a string or an instanceof ' . UriInterface::class . '.';
			//
			throw new InvalidArgumentException($message);
		}
		//
		return $this->matchAndSetParameters($requestUri);
	}

	/**
	 * Returns the route name
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Returns the route path
	 *
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * Returns the route handler
	 *
	 * @return mixed
	 */
	public function getHandler()
	{
		return $this->handler;
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

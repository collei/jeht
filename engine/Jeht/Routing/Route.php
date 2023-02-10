<?php
namespace Jeht\Routing;

use Jeht\Support\Arr;
use Jeht\Support\Str;
use InvalidArgumentException;

use Jeht\Interfaces\Routing\RouteInterface;
use Jeht\Interfaces\Http\Request;
use Psr\Http\Message\UriInterface;

/**
 * Represents a Route in the system.
 *
 */
class Route implements RouteInterface
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

	/**
	 * Set the methods this route must respond to
	 *
	 * @param	string|array	...$httpMethods
	 * @return	void
	 */
	protected function setHttpMethods($httpMethods)
	{
		$httpMethods = is_array($httpMethods) ? $httpMethods : func_get_args();
		//
		if (empty($httpMethods)) {
			$this->httpMethods = array('GET','HEAD');
			return;
		}
		//
		$this->httpMethods = Arr::wrap($httpMethods);
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
		//
		$regex = !empty($regex) ? $regex : str_replace('/', '\\/', $path);
		//
		$this->regex = "#^{$regex}\s*$#";
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
	 * @param Jeht\Interfaces\Http\Request $request
	 * @return bool
	 */
	public function matches(Request $request)
	{
		$method = $request->getMethod();
		$uri = $request->getUri()->getPath();
		//
		if (! in_array($method, $this->httpMethods)) {
			return false;
		}
		//
		return $this->matchAndSetParameters($uri);
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

	public function runRoute(Request $request)
	{
		return (new RouteDispatcher)->dispatch($request, $this->handler);
	}

}

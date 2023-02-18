<?php
namespace Jeht\Routing;

use LogicException;
use InvalidArgumentException;
use Jeht\Support\Arr;
use Jeht\Support\Str;
use Jeht\Container\Container;

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
	private $uri;

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
	private $action;

	/**
	 * @var array
	 */
	private $parameters;

	/**
	 * @var array
	 */
	private $originalParameters;

	/**
	 * @var bool
	 */
	private $isFallback = false;

	/**
	 * @var \Jeht\Routing\Router
	 */
	protected $router;

	/**
	 * @var \Jeht\Container\Container
	 */
	protected $container;

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
		$this->httpMethods = $httpMethods;
	}

	/**
	 * Builds a new Route
	 *
	 * @param string|array $httpMethods
	 * @param string $uri
	 * @param mixed $action
	 * @param string|null $regex
	 * @param string|null $name
	 */
	public function __construct(
		$methods, string $uri, $action, string $regex = null, string $name = null
	) {
		$this->setHttpMethods($methods);
		//
		$this->name = $name ?? Str::randomize();
		$this->uri = $uri;
		$this->handler = $action;
		//
		$this->action = $this->parseAction($action);
		//
		$regex = !empty($regex) ? $regex : str_replace('/', '\\/', $uri);
		//
		$this->regex = $regex;
	}

	/**
	 * Parse the route action into a standard array.
	 *
	 * @param  callable|array|null  $action
	 * @return array
	 *
	 * @throws \UnexpectedValueException
	 */
	protected function parseAction($action)
	{
		return RouteAction::parse($this->uri, $action);
	}

	/**
	 * Run the route action and return the response.
	 *
	 * @return mixed
	 */
	public function run()
	{
		$this->container = $this->container ?: new Container;

		try {
			if ($this->isControllerAction()) {
				return $this->runController();
			}

			return $this->runCallable();
		} catch (HttpResponseException $e) {
			return $e->getResponse();
		}
	}

	/**
	 * Checks whether the route's action is a controller.
	 *
	 * @return bool
	 */
	protected function isControllerAction()
	{
		return is_string($this->action['uses']) && ! $this->isSerializedClosure();
	}

	/**
	 * Run the route action and return the response.
	 *
	 * @return mixed
	 */
	protected function runCallable()
	{
		$callable = $this->action['uses'];

		if ($this->isSerializedClosure()) {
			$callable = unserialize($this->action['uses'])->getClosure();
		}

		return $callable(
			...array_values(
				$this->resolveMethodDependencies(
					$this->parametersWithoutNulls(), new ReflectionFunction($callable)
				)
			)
		);
	}

	/**
	 * Determine if the route action is a serialized Closure.
	 *
	 * @return bool
	 */
	protected function isSerializedClosure()
	{
		return RouteAction::containsSerializedClosure($this->action);
	}

	/**
	 * Get the domain defined for the route.
	 *
	 * @return string|null
	 */
	public function getDomain()
	{
		return isset($this->action['domain'])
				? str_replace(['http://', 'https://'], '', $this->action['domain'])
				: null;
	}

	/**
	 * Get the action array or one of its properties for the route.
	 *
	 * @param  string|null  $key
	 * @return mixed
	 */
	public function getAction($key = null)
	{
		return Arr::get($this->action, $key);
	}

	/**
	 * Run the route action and return the response.
	 *
	 * @return mixed
	 *
	 * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 */
	protected function runController()
	{
		return $this->controllerDispatcher()->dispatch(
			$this, $this->getController(), $this->getControllerMethod()
		);
	}

	/**
	 * Get the controller instance for the route.
	 *
	 * @return mixed
	 */
	public function getController()
	{
		if (! $this->controller) {
			$class = $this->parseControllerCallback()[0];

			$this->controller = $this->container->make(ltrim($class, '\\'));
		}

		return $this->controller;
	}

	/**
	 * Get the controller method used for the route.
	 *
	 * @return string
	 */
	protected function getControllerMethod()
	{
		return $this->parseControllerCallback()[1];
	}

	/**
	 * Parse the controller.
	 *
	 * @return array
	 */
	protected function parseControllerCallback()
	{
		return Str::parseCallback($this->action['uses']);
	}

	/**
	 * Get the compiled regex expression for the uri.
	 *
	 * @return string
	 */
	public function regex()
	{
		return $this->regex;
	}

	/**
	 * Get the uri.
	 *
	 * @return string
	 */
	public function uri()
	{
		return $this->uri;
	}

	/**
	 * Checks if the given $requestUri matches the route. 
	 *
	 * @param string $requestUri
	 * @return bool
	 */
	protected function matchesUri(string $requestUri)
	{
		return $this->router->requestMatchesRegex($requestUri, $this->regex);
	}

	/**
	 * Checks if the given $requestUri matches the route.
	 *
	 * @param Jeht\Interfaces\Http\Request $request
	 * @param bool $includingMethod
	 * @return bool
	 */
	public function matches(Request $request, bool $includingMethod = true)
	{
		$uri = $request->getUri()->getPath();
		//
		if ($includingMethod) {
			if (! in_array($request->getMethod(), $this->httpMethods, true)) {
				return false;
			}
		}
		//
		return $this->matchesUri($uri);
	}

	/**
	 * Bind the route to a given $request for execution.
	 *
	 * @param \Jeht\Http\Request $request
	 * @return $this
	 */
	public function bind(Request $request)
	{
		$this->parameters = $this->router->fetchParameterValuesFromUri(
			$request->getUri()->getPath(), $this->regex
		);
		//
		$this->originalParameters = $this->parameters;
		//
		return $this;
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
	 * Returns the route uri
	 *
	 * @return string
	 */
	public function getUri()
	{
		return $this->uri;
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
	 * Checks if the route has parameters.
	 *
	 * @return bool
	 */
	public function hasParameters()
	{
		return isset($this->parameters);
	}

	/**
	 * Checks if the route parameter $name exists.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function hasParameter(string $name)
	{
		if ($this->hasParameters()) {
			return array_key_exists($name, $this->parameters());
		}
		//
		return false;
	}

	/**
	 * Returns all route parameters.
	 *
	 * @return $array
	 * @throws \LogicException
	 */
	public function parameters()
	{
		if ($this->hasParameters()) {
			return $this->parameters;
		}
		//
		throw new LogicException('Route is not bound.');
	}

	/**
	 * Get a given parameter from the route.
	 *
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public function parameter(string $name, $default = null)
	{
		return Arr::get($this->parameters(), $name, $default);
	}

	/**
	 * Set a parameter to the given route.
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	public function setParameter(string $name, $value)
	{
		$this->parameters();
		//
		$this->parameters[$name] = $value;
	}

	/**
	 * Unset a parameter on the route.
	 *
	 * @param string $name
	 * @return void
	 */
	public function forgetParameter(string $name)
	{
		$this->parameters();
		//
		unset($this->parameters[$name]);
	}

	/**
	 * Get a key/value list of parameters without null values.
	 *
	 * @return array
	 */
	public function parametersWithoutNulls()
	{
		return array_filter($this->hasParameters(), function($val) {
			return !is_null($val);
		});
	}

	/**
	 * Mark this route as a fallback route.
	 *
	 * @return $this
	 */
	public function fallback()
	{
		$this->isFallback = true;
		//
		return $this;
	}

	/**
	 * Set the fallback value.
	 *
	 * @param bool $isFallback
	 * @return $this
	 */
	public function setFallback(bool $isFallback)
	{
		$this->isFallback = $isFallback;
		//
		return $this;
	}

	/**
	 * Returns whether the route is a fallback.
	 *
	 * @return bool
	 */
	public function isFallback()
	{
		return $this->isFallback;
	}

	/**
	 * Set the router.
	 *
	 * @param \Jeht\Routing\Route $router
	 * @return $this
	 */
	public function setRouter(Router $router)
	{
		$this->router = $router;
		//
		return $this;
	}

	/**
	 * Set the container.
	 *
	 * @param \Jeht\Container\Container $container
	 * @return $this
	 */
	public function setContainer(Container $container)
	{
		$this->container = $container;
		//
		return $this;
	}

	/**
	 * Get the HTTP verbs the route responds to.
	 *
	 * @return array
	 */
	public function methods()
	{
		return $this->httpMethods;
	}


	public function runRoute(Request $request)
	{
		return (new RouteDispatcher)->dispatch($request, $this->handler);
	}

	/**
	 * Dynamically access route parameters
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->parameter($key);
	} 

}


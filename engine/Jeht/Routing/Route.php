<?php
namespace Jeht\Routing;

use LogicException;
use InvalidArgumentException;
use Jeht\Support\Arr;
use Jeht\Support\Str;
use Jeht\Collections\Collection;
use Jeht\Container\Container;
use Jeht\Interfaces\Routing\RouteInterface;
use Jeht\Interfaces\Routing\ControllerDispatcherInterface;
use Jeht\Interfaces\Http\Request;
use Psr\Http\Message\UriInterface;
use Laravel\SerializableClosure\SerializableClosure;
use Jeht\Support\Traits\CallerAware;

/**
 * Represents a Route in the system.
 *
 */
class Route implements RouteInterface
{
	use CallerAware;

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
	 * @var array
	 */
	protected $computedMiddleware;

	/**
	 * @var array
	 */
	protected $compiled;

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
		if ($action) {
			$this->action = $this->parseAction($action);
		}
		//
		$regex = !empty($regex) ? $regex : str_replace('/', '\\/', $uri);
		//
		$this->regex = $regex;
	}

	/**
	 * Builds a Route instance from a CompiledRoute instance.
	 *
	 * @param \Jeht\Http\CompiledRoute
	 * @return static
	 */
	public static function fromCompiledRoute(CompiledRoute $compiled)
	{
		$route = (new self(
			$compiled->getMethods(),
			$compiled->getUri(),
			null,
			$compiled->getRegex(),
			$compiled->getName()
		))->setFallback($compiled->isFallback());
		//
		$route->action = $compiled->getAction();
		$route->parameters = $compiled->getParameters();
		$route->computedMiddleware = $compiled->getMiddleware();
		//
		return $route;
	}

	/**
	 * Compiles the route and returns it.
	 *
	 * @return \Jeht\Http\CompiledRoute
	 */
	public function compile()
	{
		return ($this->compiled = new CompiledRoute(
			$this->name,
			$this->httpMethods,
			$this->uri,
			$this->regex,
			$this->action,
			$this->originalParameters ?: $this->parameters ?: [],
			$this->isFallback,
			$this->computedMiddleware ?: []
		));
	}

	/**
	 * Compiles the route and returns it.
	 *
	 * @return $this
	 */
	protected function decompile()
	{
		if ($this->compiled) {
			$this->name = $this->compiled->getName();
			$this->httpMethods = $this->compiled->getMethods();
			$this->uri = $this->compiled->getUri();
			$this->regex = $this->compiled->getRegex();
			$this->action = $this->compiled->getAction();
			$this->parameters = $this->compiled->getParameters();
			$this->originalParameters ?: $this->parameters ?: [];
			$this->isFallback = $this->compiled->isFallback();
			$this->computedMiddleware = $this->compiled->getMiddleware() ?: [];
		}
		//
		return $this;
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
	 * Get the dispatcher for the route's controller.
	 *
	 * @return \Jeht\Interfaces\Routing\ControllerDispatcherInterface
	 */
	public function controllerDispatcher()
	{
		if ($this->container->bound(ControllerDispatcherInterface::class)) {
			return $this->container->make(ControllerDispatcherInterface::class);
		}

		return new ControllerDispatcher($this->container);
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
	public function getControllerClass()
	{
		return $this->parseControllerCallback()[0];
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
		return array_filter($this->parameters(), function($val) {
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

	/**
	 * Dynamically access route parameters
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __get($key)
	{
		if (property_exists($this, $key)) {
			$caller = $this->getCallerClassName();
			$allowed = [static::class, \Jeht\Routing\RouteCollection::class];

			if (in_array($caller, $allowed)) {
				return $this->$key;
			}
		}

		return $this->parameter($key);
	} 

	/**
	 * Get the value of the action that should be taken on a missing model exception.
	 *
	 * @return \Closure|null
	 */
	public function getMissing()
	{
		$missing = $this->action['missing'] ?? null;

		return is_string($missing) &&
			Str::startsWith($missing, [
				'O:47:"Laravel\\SerializableClosure\\SerializableClosure',
			]) ? unserialize($missing) : $missing;
	}

	/**
	 * Define the callable that should be invoked on a missing model exception.
	 *
	 * @param  \Closure  $missing
	 * @return $this
	 */
	public function missing($missing)
	{
		$this->action['missing'] = $missing;

		return $this;
	}

	/**
	 * Get all middleware, including the ones from the controller.
	 *
	 * @return array
	 */
	public function gatherMiddleware()
	{
		if (! is_null($this->computedMiddleware)) {
			return $this->computedMiddleware;
		}

		$this->computedMiddleware = [];

		return $this->computedMiddleware = Router::uniqueMiddleware(
			array_merge(
				$this->middleware(), $this->controllerMiddleware()
			)
		);
	}

	/**
	 * Get or set the middlewares attached to the route.
	 *
	 * @param  array|string|null  $middleware
	 * @return $this|array
	 */
	public function middleware($middleware = null)
	{
		if (is_null($middleware)) {
			return (array) ($this->action['middleware'] ?? []);
		}

		if (! is_array($middleware)) {
			$middleware = func_get_args();
		}

		foreach ($middleware as $index => $value) {
			$middleware[$index] = (string) $value;
		}

		$this->action['middleware'] = array_merge(
			(array) ($this->action['middleware'] ?? []), $middleware
		);

		return $this;
	}

	/**
	 * Specify that the "Authorize" / "can" middleware should be applied
	 * to the route with the given options.
	 *
	 * @param  string  $ability
	 * @param  array|string  $models
	 * @return $this
	 */
	public function can($ability, $models = [])
	{
		return empty($models)
					? $this->middleware(['can:'.$ability])
					: $this->middleware(['can:'.$ability.','.implode(',', Arr::wrap($models))]);
	}

	/**
	 * Get the middleware for the route's controller.
	 *
	 * @return array
	 */
	public function controllerMiddleware()
	{
		if (! $this->isControllerAction()) {
			return [];
		}

		[$controllerClass, $controllerMethod] = [
			$this->getControllerClass(),
			$this->getControllerMethod(),
		];

		if (is_a($controllerClass, HasMiddleware::class, true)) {
			return $this->staticallyProvidedControllerMiddleware(
				$controllerClass, $controllerMethod
			);
		}

		if (method_exists($controllerClass, 'getMiddleware')) {
			return $this->controllerDispatcher()->getMiddleware(
				$this->getController(), $controllerMethod
			);
		}

		return [];
	}

	/**
	 * Get the statically provided controller middleware for the given class and method.
	 *
	 * @param  string  $class
	 * @param  string  $method
	 * @return array
	 */
	protected function staticallyProvidedControllerMiddleware(string $class, string $method)
	{
		return Collection::for($class::middleware())->reject(function ($middleware) use ($method) {
			return $this->controllerDispatcher()::methodExcludedByOptions(
				$method, ['only' => $middleware->only, 'except' => $middleware->except]
			);
		})->map->middleware->values()->all();
	}

	/**
	 * Specify middleware that should be removed from the given route.
	 *
	 * @param  array|string  $middleware
	 * @return $this
	 */
	public function withoutMiddleware($middleware)
	{
		$this->action['excluded_middleware'] = array_merge(
			(array) ($this->action['excluded_middleware'] ?? []), Arr::wrap($middleware)
		);

		return $this;
	}

	/**
	 * Get the middleware should be removed from the route.
	 *
	 * @return array
	 */
	public function excludedMiddleware()
	{
		return (array) ($this->action['excluded_middleware'] ?? []);
	}

	/**
	 * Prepares for the serialization process.
	 *
	 * @return void
	 * /
	public function __sleep()
	{
		$this->router = null;
		$this->container = null;
		//
		return [
			'name','methods','uri','regex','action','parameters',
			'fallback','middleware','router','container'
		];
	}

	/**
	 * Restores the object state from a cache or a slumber file.
	 *
	 * @return static
	 * /
	public static function __set_state(array $data): object
	{
		return new self(
			$data['name'],
			$data['methods'],
			$data['uri'],
			$data['regex'],
			$data['action'],
			$data['parameters'],
			$data['fallback'],
			$data['middleware']
		);
	}

	/**
	 * Returns an array of properties to be serialized
	 *
	 * @return array
	 * /
	public function __serialize()
	{
		$name = $this->name;
		$methods = $this->httpMethods;
		$uri = $this->uri;
		$regex = $this->regex;
		$action = $this->action;
		$parameters = $this->parameters;
		$fallback = $this->isFallback();
		$middleware = $this->computedMiddleware;
		//
		$action['uses'] = $this->compileIfClosure($action['uses']);
		//
		$router = $container = null;
		//
		return compact(
			'name','methods','uri','regex','action','parameters',
			'fallback','middleware','router','container'
		);
	}

	/**
	 * Compiles into a PHP serialized format
	 *
	 * @return string
	 * /
	public function serialize()
	{
		return serialize($this->__serialize());
	}

	/**
	 * Restores data from a PHP serialized format.
	 *
	 * @param string $data
	 * @return void
	 * /
	public function __unserialize(array $data)
	{
		$data['action']['uses'] = $this->restoreIfCompiledClosure(
			$data['action']['uses'] ?? 'undefineda'
		);
		//
		$this->name = $data['name'];
		$this->httpMethods = $data['methods'];
		$this->uri = $data['uri'];
		$this->regex = $data['regex'];
		$this->action = $data['action'];
		$this->parameters = $data['parameters'];
		$this->fallback = $data['fallback'];
		$this->computedMiddleware = $data['middleware'];
	}

	/**
	 * Restores data from a PHP serialized format.
	 *
	 * @param string $data
	 * @return void
	 * /
	public function unserialize(string $data)
	{
		$restored = unserialize($data);
		//
		$this->__unserialize($restored);
	}
	///*/

}


<?php
namespace Jeht\Http;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

use Jeht\Http\Interfaces\Request as RequestInterface;

class Request implements RequestInterface
{
	/**
	 * @var array METHODS
	 */
	public const METHODS = ['GET','HEAD','POST','PUT','DELETE','CONNECT','OPTIONS','TRACE','PATCH'];

	/**
	 * @var UriInterface
	 */
	protected $uri;

	/**
	 * @var string
	 */
	protected $requestTarget = '/';

	/**
	 * @var string
	 */
	protected $method = '';

	/**
	 * @var string
	 */
	protected $httpVersion = '';

	/**
	 * @var string[]
	 */
	protected $headers = [];

	/**
	 * @var string
	 */
	protected $body = '';

	/**
	 * @var array
	 */
	protected $serverParams;

	/**
	 * @var array
	 */
	protected $cookieParams;

	/**
	 * @var array
	 */
	protected $queryStringParams;

	/**
	 * @var array
	 */
	protected $uploadedFiles;

	/**
	 * @var mixed
	 */
	protected $parsedBodyContent;

	/**
	 * @var array
	 */
	protected $attributes;

	/**
	 * @var \Jeht\Routing\Route
	 */
	protected $route;

	/**
	 * Instantiates a HttpRequest object
	 *
	 */
	public function __construct()
	{
		$this->method = $_SERVER['REQUEST_METHOD'];
		//
		$this->uri = (new UriFactory)->createUri($_SERVER['REQUEST_URI']);
	}

	/**
	 * Retrieves the message's request target.
	 *
	 * @return string
	 */
	public function getRequestTarget()
	{
		return empty($this->requestTarget) ? '/' : $this->requestTarget;
	}

	/**
	 * Return an instance with the specific request-target.
	 *
	 * @see http://tools.ietf.org/html/rfc7230#section-5.3 (for the various
	 *	 request-target forms allowed in request messages)
	 * @param mixed $requestTarget
	 * @return static
	 */
	public function withRequestTarget($requestTarget)
	{
		$new = new static;
		$new->requestTarget = $requestTarget;
		return $new;
	}

	/**
	 * Retrieves the HTTP method of the request.
	 *
	 * @return string Returns the request method.
	 */
	public function getMethod()
	{
		return $this->method;
	}

	/**
	 * Tells if $method is the HTTP method of the request.
	 *
	 * @return bool
	 */
	public function isMethod(string $method)
	{
		return strcasecmp($method, $this->method) === 0;
	}

	/**
	 * Return an instance with the provided HTTP method.
	 *
	 * While HTTP method names are typically all uppercase characters,
	 * HTTP method names are case-sensitive and thus implementations
	 * SHOULD NOT modify the given string.
	 *
	 * This method MUST be implemented in such a way as to retain the
	 * immutability of the message, and MUST return an instance that has the
	 * changed request method.
	 *
	 * @param string $method Case-sensitive method.
	 * @return static
	 * @throws \InvalidArgumentException for invalid HTTP methods.
	 */
	public function withMethod($method)
	{
		if (!in_array(strtoupper($method), self::METHODS)) {
			throw new InvalidArgumentException(
				'Method must be one of these: ' . implode(', ', self::METHODS)
			);
		}
		//
		$new = new static;
		$new->method = $method;
		return $new;
	}

	/**
	 * Retrieves the URI instance.
	 *
	 * @see http://tools.ietf.org/html/rfc3986#section-4.3
	 *
	 * @return \Psr\Http\Message\UriInterface
	 */
	public function getUri()
	{
		return $this->uri;
	}

	/**
	 * Checks whether the request is secure or not.
	 *
	 * @return bool
	 */
	public function isSecure()
	{
		$https = $this->getServerParam('HTTPS');
		//
		return !empty($https) && 'off' !== strtolower($https);
	}

	/**
	 * Returns an instance with the provided URI.
	 *
	 * @see http://tools.ietf.org/html/rfc3986#section-4.3
	 * @param UriInterface $uri New request URI to use.
	 * @param bool $preserveHost Preserve the original state of the Host header.
	 * @return static
	 */
	public function withUri(UriInterface $uri, $preserveHost = false)
	{
		$new = new static;
		$host = $uri->getHost();
		$new->uri = $uri;
		//
		// If the new URI contains a host component...
		if (!empty($host)) {
			// If original host header must be preserved...
			if ($preserveHost) {
				$header = $this->getHeader('Host');
				// If the Host header is missing or empty...
				if (empty($header)) {
					// then adds the Host header in the returned request
					$new = $new->withHeader('Host', $host);
				}
			} else {
				// updates the Host header in the returned request
				$new = $new->withHeader('Host', $host);
			}
		}
		//
		return $new;
	}

	/**
	 * Retrieves the HTTP protocol version as a string.
	 *
	 * @return string HTTP protocol version.
	 */
	public function getProtocolVersion()
	{
		return $this->httpVersion;
	}

	/**
	 * Return an instance with the specified HTTP protocol version.
	 *
	 * @param string $version HTTP protocol version
	 * @return static
	 */
	public function withProtocolVersion($version)
	{
		$new = new static;
		$new->httpVersion = $version;
		return $new;
	}

	/**
	 * Retrieves all message header values.
	 *
	 * @return string[][] Returns an associative array of the message's headers.
	 *	 Each key MUST be a header name, and each value MUST be an array of
	 *	 strings for that header.
	 */
	public function getHeaders()
	{
		return $this->headers;
	}

	/**
	 * Checks if a header exists by the given case-insensitive name.
	 *
	 * @param string $name Case-insensitive header field name.
	 * @return bool Returns true if any header names match the given header
	 *	 name using a case-insensitive string comparison. Returns false if
	 *	 no matching header name is found in the message.
	 */
	public function hasHeader($name)
	{
		if (empty($this->headers)) {
			return false;
		}
		//
		foreach ($this->headers as $n => $v) {
			if (0 == strcasecmp($n, $name)) {
				return true;
			}
		}
		//
		return false;
	}

	/**
	 * Retrieves a message header value by the given case-insensitive name.
	 *
	 * @param string $name Case-insensitive header field name.
	 * @return string[] An array of string values as provided for the given
	 *	header. If the header does not appear in the message, this method MUST
	 *	return an empty array.
	 */
	public function getHeader($name)
	{
		if (empty($this->headers)) {
			return [];
		}
		//
		foreach ($this->headers as $n => $v) {
			if (0 == strcasecmp($n, $name)) {
				return is_array($v) ? $v : [$v];
			}
		}
		//
		return [];
	}	

	/**
	 * Retrieves a comma-separated string of the values for a single header.
	 *
	 * @param string $name Case-insensitive header field name.
	 * @return string A string of values as provided for the given header
	 *	concatenated together using a comma. If the header does not appear in
	 *	the message, this method MUST return an empty string.
	 */
	public function getHeaderLine($name)
	{
		if (empty($this->headers)) {
			return '';
		}
		//
		foreach ($this->headers as $n => $v) {
			if (0 == strcasecmp($n, $name)) {
				return is_array($v) ? implode(',', $v) : $v;
			}
		}
		//
		return '';
	}

	/**
	 * Return an instance with the provided value replacing the specified header.
	 *
	 * @param string $name Case-insensitive header field name.
	 * @param string|string[] $value Header value(s).
	 * @return static
	 * @throws \InvalidArgumentException for invalid header names or values.
	 */
	public function withHeader($name, $value)
	{
		if (!is_string($name)) {
			throw new InvalidArgumentException('Name must be a string');
		}
		//
		if (!is_string($value) && !is_array($value)) {
			throw new InvalidArgumentException('Value must be a string or array');
		}
		//
		$new = new static;
		//
		if (empty($new->headers)) {
			$new->headers = [
				$name => is_array($value) ? $value : [$value]
			];
		} else {
			$found = false;
			foreach ($new->headers as $n => $v) {
				if (0 == strcasecmp($n, $name)) {
					$new->headers[$n] = is_array($value) ? $value : [$value];
					$found = true;
					break;
				}
			}
			//
			if (!$found) {
				$new->headers[$name] = is_array($value) ? $value : [$value];
			}
		}
		//
		return $new;
	}

	/**
	 * Return an instance with the specified header appended with the given value.
	 *
	 * @param string $name Case-insensitive header field name to add.
	 * @param string|string[] $value Header value(s).
	 * @return static
	 * @throws \InvalidArgumentException for invalid header names.
	 * @throws \InvalidArgumentException for invalid header values.
	 */
	public function withAddedHeader($name, $value)
	{
		if (!is_string($name)) {
			throw new InvalidArgumentException('Name must be a string');
		}
		//
		if (!is_string($value) && !is_array($value)) {
			throw new InvalidArgumentException('Value must be a string or array');
		}
		//
		$new = new static;
		//
		if (empty($new->headers)) {
			$new->headers = [
				$name => is_array($value) ? $value : [$value]
			];
		} else {
			$found = false;
			foreach ($new->headers as $n => $v) {
				if (0 == strcasecmp($n, $name)) {
					if (!is_array($v)) {
						$new->headers[$n] = [$v];
					}
					//
					if (is_array($value)) {
						foreach ($value as $valueItem) {
							$new->headers[$n][] = $valueItem;
						}
					} else {
						$new->headers[$n][] = $value;
					}
					//
					$found = true;
					break;
				}
			}
			//
			if (!$found) {
				$new->headers[$name] = is_array($value) ? $value : [$value];
			}
		}
		//
		return $new;
	}

	/**
	 * Return an instance without the specified header.
	 *
	 * @param string $name Case-insensitive header field name to remove.
	 * @return static
	 */
	public function withoutHeader($name)
	{
		if (!is_string($name)) {
			throw new InvalidArgumentException('Name must be a string');
		}
		//
		$new = new static;
		//
		if (!empty($new->headers)) {
			foreach ($new->headers as $n => $v) {
				if (0 == strcasecmp($n, $name)) {
					unset($new->headers[$n]);
					break;
				}
			}
		}
		//
		return $new;
	}

	/**
	 * Gets the body of the message.
	 *
	 * @return StreamInterface Returns the body as a stream.
	 */
	public function getBody()
	{
		return $this->body;
	}

	/**
	 * Return an instance with the specified message body.
	 *
	 * @param StreamInterface $body Body.
	 * @return static
	 * @throws \InvalidArgumentException When the body is not valid.
	*/
	public function withBody(StreamInterface $body)
	{
		$new = new static;
		$new->body = $body;
		return $new;
	}

	/**
	 * Retrieve the given server parameter.
	 *
	 * @param string $name
	 * @param mixed $default
	 * @return string
	 */
	public function getServerParam(string $name, $default = null)
	{
		return $this->serverParams[$name] ?? $default;
	}

	/**
	 * Retrieve server parameters.
	 *
	 * @return array
	 */
	public function getServerParams()
	{
		return $this->serverParams;
	}

	/**
	 * Return an instance with the specified server parameters.
	 *
	 * @param array $server Array of server parameters, typically from $_SERVER.
	 * @return static
	 */
	public function withServerParams(array $server)
	{
		$cloned = clone $this;
		$cloned->serverParams = $server;
		return $cloned;
	}

	/**
	 * Retrieves cookies sent by the client to the server.
	 *
	 * @return array
	 */
	public function getCookieParams()
	{
		return $this->cookieParams;
	}

	/**
	 * Return an instance with the specified cookies.
	 *
	 * @param array $cookies Array of key/value pairs representing cookies.
	 * @return static
	 */
	public function withCookieParams(array $cookies)
	{
		$cloned = clone $this;
		//
		$cloned->cookieParams = $cookies;
		//
		return $cloned;
	}

	/**
	 * Retrieve query string arguments.
	 *
	 * @return array
	 */
	public function getQueryParams()
	{
		return $this->queryStringParams;
	}

	/**
	 * Return an instance with the specified query string arguments.
	 *
	 * @param array $query Array of query string arguments, typically from
	 *	 $_GET.
	 * @return static
	 */
	public function withQueryParams(array $query)
	{
		$cloned = clone $this;
		//
		$cloned->queryStringParams = $query;
		//
		return $cloned;
	}

	/**
	 * Retrieve normalized file upload data.
	 *
	 * @return array An array tree of UploadedFileInterface instances; an empty
	 *	 array MUST be returned if no data is present.
	 */
	public function getUploadedFiles()
	{
		return $this->uploadedFiles;
	}

	/**
	 * Create a new instance with the specified uploaded files.
	 *
	 * @param array $uploadedFiles An array tree of UploadedFileInterface instances.
	 * @return static
	 * @throws \InvalidArgumentException if an invalid structure is provided.
	 */
	public function withUploadedFiles(array $uploadedFiles)
	{
		$cloned = clone $this;
		//
		$cloned->uploadedFiles = $uploadedFiles;
		//
		return $cloned;
	}

	/**
	 * Retrieve any parameters provided in the request body.
	 *
	 * @return null|array|object The deserialized body parameters, if any.
	 *	 These will typically be an array or object.
	 */
	public function getParsedBody()
	{
		return $this->parsedBodyContent;
	}

	/**
	 * Return an instance with the specified body parameters.
	 *
	 * @param null|array|object $data The deserialized body data. This will
	 *	 typically be in an array or object.
	 * @return static
	 * @throws \InvalidArgumentException if an unsupported argument type is
	 *	 provided.
	 */
	public function withParsedBody($data)
	{
		$cloned = clone $this;
		//
		$cloned->parsedBodyContent = $data;
		//
		return $cloned;
	}

	/**
	 * Retrieve attributes derived from the request.
	 *
	 * The request "attributes" may be used to allow injection of any
	 * parameters derived from the request: e.g., the results of path
	 * match operations; the results of decrypting cookies; the results of
	 * deserializing non-form-encoded message bodies; etc. Attributes
	 * will be application and request specific, and CAN be mutable.
	 *
	 * @return mixed[] Attributes derived from the request.
	 */
	public function getAttributes()
	{
		return $this->attributes;
	}

	/**
	 * Returns if the specified request attribute exists.
	 *
	 * @see getAttributes()
	 * @param string $name The attribute name.
	 * @return bool
	 */
	public function hasAttribute($name)
	{
		return array_key_exists($name, $this->attributes);
	}

	/**
	 * Retrieve a single derived request attribute.
	 *
	 * @see getAttributes()
	 * @param string $name The attribute name.
	 * @param mixed $default Default value to return if the attribute does not exist.
	 * @return mixed
	 */
	public function getAttribute($name, $default = null)
	{
		return $this->attributes[$name] ?? $default;
	}

	/**
	 * Return an instance with the specified derived request attribute.
	 *
	 * @see getAttributes()
	 * @param string $name The attribute name.
	 * @param mixed $value The value of the attribute.
	 * @return static
	 */
	public function withAttribute($name, $value)
	{
		$cloned = clone $this;
		//
		$cloned->attributes[$name] = $value;
		//
		return $cloned;
	}

	/**
	 * Return an instance that removes the specified derived request attribute.
	 *
	 * @see getAttributes()
	 * @param string $name The attribute name.
	 * @return static
	 */
	public function withoutAttribute($name)
	{
		$cloned = clone $this;
		//
		unset($cloned->attributes[$name]);
		//
		return $cloned;
	}

	/**
	 * Returns the route bound with the request.
	 *
	 * @return \Jeht\Routing\Route
	 */
	public function route()
	{
		return $this->route;
	}

	/**
	 * Captures a request with a help of its factory
	 *
	 * @return \Jeht\Http\Request\Request
	 */
	public static function capture()
	{
		return RequestFactory::captureRequest();
	}

	/**
	 * Creates a Request based on a given URI and configuration.
	 *
	 * The information contained in the URI always take precedence
	 * over the other information (server and parameters).
	 *
	 * @param string	$uri		The URI
	 * @param string	$method		The HTTP method
	 * @param array		$parameters	The query (GET) or request (POST) parameters
	 * @param array		$cookies	The request cookies ($_COOKIE)
	 * @param array		$files		The request files ($_FILES)
	 * @param array		$server		The server parameters ($_SERVER)
	 * @param string|resource|null $content	The raw body data
	 * @return Jeht\Http\Request
	 */
	public static function create(
		string $uri, string $method = 'GET',
		array $parameters = [],
		array $cookies = [],
		array $files = [],
		array $server = [],
		$content = null
	) {
		return (new RequestFactory)->createFromParts(
			$uri, $method, $parameters, $cookies, $files, $server, $content
		);
	}

	/**
	 * Returns the given $name field value from the POST and GET fields.
	 * POST fields are priorized.
	 *
	 * @param string $name = null
	 * @param mixed $default
	 * @return mixed 
	 */
	public function input(string $name = null, $default = null)
	{
		if (is_null($name)) {
			if (is_array($this->parsedBodyContent)) {
				return $this->parsedBodyContent + $this->queryStringParams;
			}
			//
			return $this->queryStringParams;
		}
		//
		if (is_array($this->parsedBodyContent)) {
			return $this->parsedBodyContent[$name]
				?? $this->queryStringParams[$name]
				?? $default;
		}
		//
		if (is_object($this->parsedBodyContent)) {
			return Arr::get(
				$this->parsedBodyContent,
				$name,
				$this->queryStringParams[$name] ?? $default
			);
		}
		//
		return $default;
	}

	/**
	 * Returns the server parameter $name, or null if not found.
	 * If parameter is ommited, returns all server parameters at once.
	 *
	 * @param string $name
	 * @return string|array|null
	 */
	public function server(string $name = null)
	{
		if ($name) {
			return $this->serverParams[$name]
				?? $this->serverParams[strtoupper($name)]
				?? null;
		}
		//
		return $this->serverParams;
	}

	/**
	 * Returns the query parameter $name, or null if not found.
	 * If parameter is ommited, returns all query parameters at once.
	 *
	 * @param string $name
	 * @return string|array|null
	 */
	public function query(string $name = null)
	{
		if ($name) {
			return $this->queryStringParams[$name] ?? null;
		}
		//
		return $this->queryStringParams;
	}

	/**
	 * Alias of input()
	 *
	 * @param string $name = null
	 * @param mixed $default
	 * @return mixed 
	 */
	public function request(string $name = null, $default = null)
	{
		return $this->input($name, $default);
	}

	/**
	 * Returns the cookie of $name, or null if not found.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function cookie(string $name)
	{
		return $this->cookieParams[$name] ?? null;
	}

	/**
	 * Returns all cookies at once, if any.
	 *
	 * @return array|null
	 */
	public function cookies()
	{
		return $this->cookieParams;
	}

	/**
	 * Returns the file of $name, or null if not found.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function file(string $name)
	{
		return Arr::get($this->uploadedFiles, $name, null);
	}

	/**
	 * Returns all files at once, if any.
	 *
	 * @return array|null
	 */
	public function files()
	{
		return $this->uploadedFiles;
	}

	/**
	 * Returns the attribute of $name, or null if not found.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function attribute(string $name)
	{
		return $this->attributes[$name] ?? null;
	}

	/**
	 * Returns all attributes at once, if any.
	 *
	 * @return array|null
	 */
	public function attributes()
	{
		return $this->attributes;
	}

}



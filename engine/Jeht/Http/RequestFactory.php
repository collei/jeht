<?php
namespace Jeht\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;

use Jeht\Support\Streams\StreamFactory;
use Jeht\Support\Arr;

class RequestFactory implements RequestFactoryInterface, ServerRequestFactoryInterface
{
	/**
	 * @var array
	 */
	protected const UPLOADED_FILE_PARAMETERS = [
		'name',
		'type',
		'tmp_name',
		'error',
		'size'
	];

	/**
	 * @var \Jeht\Support\Streams\StreamFactory
	 */
	protected $uriFactory;

	/**
	 * @var \Jeht\Support\Streams\StreamFactory
	 */
	protected $streamFactory;

	/**
	 * @var \Jeht\Http\Request\UploadedFileFactory
	 */
	protected $uploadedFileFactory;

	/**
	 * Initializes factories and stuff
	 *
	 */
	public function __construct()
	{
		$this->uriFactory = new UriFactory;
		$this->streamFactory = new StreamFactory;
		$this->uploadedFileFactory = new UploadedFileFactory;
	}

	/**
	 * Create a new request.
	 *
	 * @param string $method The HTTP method associated with the request.
	 * @param UriInterface|string $uri The URI associated with the request. 
	 * @return \Jeht\Http\Request
	 */
	public function createRequest(string $method, $uri): RequestInterface
	{
		$request = (new Request)->withMethod($method);
		//
		if ($uri instanceof UriInterface) {
			return $request->withUri($uri);
		}
		//
		$uriInterface = $this->uriFactory->createUri($uri);
		//
		return $request->withUri($uriInterface);
	}

	/**
	 * Create a new server request.
	 *
	 * @param string $method The HTTP method associated with the request.
	 * @param UriInterface|string $uri The URI associated with the request. 
	 * @param array $serverParams An array of Server API (SAPI) parameters with
	 *	 which to seed the generated request instance.
	 * @return \Jeht\Http\Request
	 */
	public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
	{
		$request = $this->createRequest($method, $uri)
			->withCookieParams($_COOKIE)
			->withQueryParams($_GET)
			->withParsedBody($_POST);
		//
		if ($uploadedFiles = $this->fetchUploadedFilesFromGlobal()) {
			$request = $request->withUploadedFiles($uploadedFiles);
		}
		//
		$request = $request->withServerParams(
			empty($serverParams) ? $serverParams : $_SERVER
		);
		//
		return $request;
	}

	/**
	 * Reaps a tree of UploadedFileInterface instances from the $_FILES global
	 *
	 * @return array
	 */
	protected function fetchUploadedFilesFromGlobal()
	{
		return $this->fetchUploadedFilesFrom(
			$array = $this->fetchNormalizedUploaded($_FILES)
		);
	}

	/**
	 * Reaps a tree of UploadedFileInterface instances from a normalized
	 * $array obtained from fetchNormalizedUploaded()
	 *
	 * @return array
	 */
	protected function fetchUploadedFilesFrom(array $array)
	{
		$self = $this;
		//
		Arr::treeMapLeafs($array, function($file) use ($self) {
			if (! empty($file['name'])) {
				return $self->createUploadedFile(
					$file['tmp_name'],
					$file['size'],
					$file['error'],
					$file['name'],
					$file['type']
				);
			} else {
				return null;
			}
		}, true);
		//
		return $array;
	}

	/**
	 * Returns a normalized version of the given $files
	 *
	 * Thenks to Mrten <https://gist.github.com/Mrten> (see link below)
	 * @link https://gist.github.com/umidjons/9893735?permalink_comment_id=3495051#gistcomment-3495051
	 *
	 * @param	array	$files	The uploaded file tree to process. Usually, the $_FILES global
	 * @return	array
	 */
	protected function fetchNormalizedUploaded(array $files) {
		$out = [];
		//
		foreach ($files as $key => $file) {
			if (isset($file['name']) && is_array($file['name'])) {
				$new = [];
				//
				foreach (self::UPLOADED_FILE_PARAMETERS as $k) {
					array_walk_recursive($file[$k], function (&$data, $key, $k) {
						$data = [$k => $data];
					}, $k);
					$new = array_replace_recursive($new, $file[$k]);
				}
				//
				$out[$key] = $new;
			} else {
				$out[$key] = $file;
			}
		}
		//
		return $out;
	}

	/**
	 * Create a new uploaded file.
	 *
	 * @see \Jeht\Http\Request\UploadedFileFactory
	 *
	 * @param string $filename The name of uploade file (usually from $file['tmp_name']).
	 * @param int $size The size of the file in bytes (usually from $file['size']).
	 * @param int $error The PHP file upload error (usually from $file['error']).
	 * @param string $clientFilename The filename as provided by the client, if any.
	 * @param string $clientMediaType The media type as provided by the client, if any.
	 *
	 * @throws \InvalidArgumentException If the file is not readable.
	 */
	protected function createUploadedFile(
		string $filename,
		int $size = null,
		int $error = \UPLOAD_ERR_OK,
		string $clientFilename = null,
		string $clientMediaType = null
	) {
		if (! is_readable($filename)) {
			throw new InvalidArgumentException("The file [{$filename}] is not readable.");
		}
		//
		$stream = $this->streamFactory->createStreamFromFile($filename, 'r');
		//
		return $this->uploadedFileFactory->createUploadedFile(
			$stream,
			$size ?? filesize($filename),
			$error,
			$clientFilename,
			$clientMediaType
		);
	}

	/**
	 * Captures and returns a request
	 *
	 * @return \Jeht\Http\Request\Request
	 */
	public static function captureRequest()
	{
		$factory = new static;
		//
		$request = $factory->createServerRequest(
			$_SERVER['REQUEST_METHOD'],
			$_SERVER['REQUEST_URI'],
			$_SERVER
		);
		//
		return $request;
	}

	/**
	 * Adapted from Symfony's Symfony\Component\HttpFoundation\Request::create
	 * @link https://github.com/symfony/symfony/blob/6.3/src/Symfony/Component/HttpFoundation/Request.php
	 * @link https://github.com/symfony/symfony/blob/6.3/src/Symfony/Component/HttpFoundation/Request.php#L321
	 *
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
	public function createFromParts(
		string $uri, string $method = 'GET',
		array $parameters = [],
		array $cookies = [],
		array $files = [],
		array $server = [],
		$content = null
	): Request {
		if (!is_null($content) && !is_resource($content) && !is_string($content)) {
			throw new InvalidArgumentException('Content must be a string or a resource');
		}
		//
		if (is_resource($content) && !Stream::resourceIsReadable($content)) {
			throw new InvalidArgumentException('Resource must be readable !');
		}

		$server = array_replace([
			'SERVER_NAME' => 'localhost',
			'SERVER_PORT' => 80,
			'HTTP_HOST' => 'localhost',
			'HTTP_USER_AGENT' => 'Jeht & Benben',
			'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
			'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5',
			'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
			'REMOTE_ADDR' => '127.0.0.1',
			'SCRIPT_NAME' => '',
			'SCRIPT_FILENAME' => '',
			'SERVER_PROTOCOL' => 'HTTP/1.1',
			'REQUEST_TIME' => time(),
			'REQUEST_TIME_FLOAT' => microtime(true),
		], $server);

		$server['PATH_INFO'] = '';
		$server['REQUEST_METHOD'] = strtoupper($method);

		$components = parse_url($uri);
		if (isset($components['host'])) {
			$server['SERVER_NAME'] = $components['host'];
			$server['HTTP_HOST'] = $components['host'];
		}

		if (isset($components['scheme'])) {
			if ('https' === $components['scheme']) {
				$server['HTTPS'] = 'on';
				$server['SERVER_PORT'] = 443;
			} else {
				unset($server['HTTPS']);
				$server['SERVER_PORT'] = 80;
			}
		}

		if (isset($components['port'])) {
			$server['SERVER_PORT'] = $components['port'];
			$server['HTTP_HOST'] .= ':'.$components['port'];
		}

		if (isset($components['user'])) {
			$server['PHP_AUTH_USER'] = $components['user'];
		}

		if (isset($components['pass'])) {
			$server['PHP_AUTH_PW'] = $components['pass'];
		}

		if (!isset($components['path'])) {
			$components['path'] = '/';
		}

		switch (strtoupper($method)) {
			case 'POST':
			case 'PUT':
			case 'DELETE':
				if (!isset($server['CONTENT_TYPE'])) {
					$server['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
				}
				// no break
			case 'PATCH':
				$request = $parameters;
				$query = [];
				break;
			default:
				$request = [];
				$query = $parameters;
				break;
		}

		$queryString = '';
		if (isset($components['query'])) {
			parse_str(html_entity_decode($components['query']), $qs);

			if ($query) {
				$query = array_replace($qs, $query);
				$queryString = http_build_query($query, '', '&');
			} else {
				$query = $qs;
				$queryString = $components['query'];
			}
		} elseif ($query) {
			$queryString = http_build_query($query, '', '&');
		}

		$server['REQUEST_URI'] = $components['path'].('' !== $queryString ? '?'.$queryString : '');
		$server['QUERY_STRING'] = $queryString;

		if (! empty($files)) {
			$files = $this->fetchUploadedFilesFrom($files);
		}

		$request = $this->createRequest($method, $uri)
			->withCookieParams($cookies)
			->withQueryParams($query)
			->withParsedBody($request)
			->withUploadedFiles($files)
			->withServerParams($server);

		if (is_string($content) || is_null($content)) {
			$request = $request->withBody(
				$this->streamFactory->createStringStream($content ?? '')
			);
		} elseif (is_resource($content)) {
			$request = $request->withBody(
				$this->streamFactory->createStream($content)->toStringStream()
			);
		}
		//
		return $request;
	}


}


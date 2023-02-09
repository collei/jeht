<?php
namespace Jeht\Http\Request;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;

use Jeht\Http\Request\Request;
use Jeht\Http\Request\UploadFileFactory;
use Jeht\Http\Uri\UriFactory;
use Jeht\Support\Streams\StreamFactory;

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
	 * @var \Jeht\Http\Request\UploadFileFactory
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
		$this->uploadedFileFactory = new UploadFileFactory;
	}

	/**
	 * Create a new request.
	 *
	 * @param string $method The HTTP method associated with the request.
	 * @param UriInterface|string $uri The URI associated with the request. 
	 */
	public function createRequest(string $method, $uri): RequestInterface
	{
		$request = (new HttpRequest)->withMethod($method);
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
		$request->serverParams = !empty($serverParams) ? $serverParams : $_SERVER;
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
		$self = $this;
		//
		$uploadedFiles = $this->fetchNormalizedUploaded($_FILES);
		//
		Arr::treeMapLeafs($uploadedFiles, function($file) use ($self) {
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
		return $received;
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

}


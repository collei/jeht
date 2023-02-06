<?php
namespace Jeht\Http\Request;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;

use Jeht\Http\Message\UploadFileFactory;
use Jeht\Http\Request\HttpRequest;
use Jeht\Http\Uri\UriFactory;

class HttpRequestFactory implements RequestFactoryInterface, ServerRequestFactoryInterface
{
	protected $streamFactory;

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
		$uriInterface = (new UriFactory)->createUri($uri);
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

	protected const UPLOADED_FILE_PARAMETERS = [
		'name',
		'type',
		'tmp_name',
		'error',
		'size'
	];

	/**
	 * Returns a normalized version of the $_FILES global
	 *
	 * Thenks to Mrten <https://gist.github.com/Mrten> (see link below)
	 * @link https://gist.github.com/umidjons/9893735?permalink_comment_id=3495051#gistcomment-3495051
	 *
	 * @return array
	 */
	protected function fetchNormalizedUploadedGlobal() {
		$out = [];
		//
		foreach ($_FILES as $key => $file) {
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

	protected function fetchArrayedUploadedFiles(array $files, $level = null)
	{
		$uploadedFiles = [];
		//
		$sentFiles = $this->fetchNormalizedUploadedGlobal();
		//
		foreach ($files as $index => $file) {
			if (is_array($file['name'])) {
				
				$others = fetchArrayedUploadedFiles($file, $index);
			} else {
				$singleUpload = $this->fetchAsUploadedFile(
					$file['tmp_name'],
					$file['error'],
					$file['name'],
					$file['type']
				);
			}
		}
	}



	protected function fetchAsUploadedFile(
		string $filename,
		int $error = \UPLOAD_ERR_OK,
		string $clientFilename = null,
		string $clientMediaType = null
	) {
		$stream = $this->streamFactory->createStreamFromFile($filename, 'r');
		//
		return $factory->createUploadedFile(
			$stream,
			filesize($filename),
			$error,
			$clientFilename,
			$clientMediaType
		);
	}

}


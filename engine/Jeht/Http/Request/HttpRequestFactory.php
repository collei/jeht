<?php
namespace Jeht\Http\Request;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Jeht\Http\Request\HttpRequest;
use Jeht\Http\Uri\UriFactory;

class HttpRequestFactory implements RequestFactoryInterface, ServerRequestFactoryInterface
{
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
			->withParsedBody($_POST)
			->withUploadedFiles($uploadedFiles);
		//
		$request->serverParams = !empty($serverParams) ? $serverParams : $_SERVER;
		//
		return $request;
	}

}


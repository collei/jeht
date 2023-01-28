<?php
namespace Ground\Http\Request;

use Psr\Http\Message\RequestFactoryInterface;
use Ground\Http\Request\HttpRequest;
use Ground\Http\Uri\UriFactory;

class HttpRequestFactory implements RequestFactoryInterface
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

}


<?php
namespace Jeht\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;

class ResponseFactory implements ResponseFactoryInterface
{
	/**
	 * Create a new response.
	 *
	 * @param int $code The HTTP status code. Defaults to 200.
	 * @param string $reasonPhrase The reason phrase to associate with the status code
	 *	 in the generated response. If none is provided, implementations MAY use
	 *	 the defaults as suggested in the HTTP specification.
	 */
	public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
	{
		return new Response('', $code, []);
	}

	/**
	 * Creates an instance with the specified body, status code and, optionally, headers.
	 *
	 * @param string $body
	 * @param int $code
	 * @param array|null $headers
	 * @throws \InvalidArgumentException For invalid status code arguments
	 */
	public function create(string $body, int $statusCode = 200, array $headers = null)
	{
		return new Response($body, $statusCode, $headers);
	}

}



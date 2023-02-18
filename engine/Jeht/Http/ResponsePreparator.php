<?php
namespace Jeht\Http;

use Jeht\Container\Container;
use JsonSerializable;
use Jeht\Interfaces\Support\Responsable;
use Jeht\Interfaces\Support\Jsonable;
use Jeht\Interfaces\Support\Arrayable;
use Jeht\Interfaces\Support\Stringable;
use Jeht\Support\ArrayObject;

class ResponsePreparator
{
	/**
	 * @var $responseFactory
	 */
	private $responseFactory;

	/**
	 * Initializes a preparator instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->responseFactory = new ResponseFactory;
	}

	/**
	 * Prepares the $response in the appropriate format.
	 *
	 * @param \Jeht\Http\Request $request
	 * @param mixed $response
	 * @return \Jeht\Http\Response
	 */
	public function prepare(Request $request, $response)
	{
		if ($response instanceof Responsable) {
			$response = $response->toResponse($request);
		}
		//
		if ($response instanceof Stringable) {
			$response = $this->create($response->__toString(), 200, ['Content-Type' => 'text/html']);
		} elseif (
			$response instanceof Arrayable ||
			$response instanceof Jsonable ||
			$response instanceof JsonSerializable ||
			$response instanceof ArrayObject ||
			is_array($response)
		) {
			$response = new JsonResponse($response);
		}

		return $response;
	}

	/**
	 * Creates an instance with the specified body, status code and, optionally, headers.
	 *
	 * @param string $body
	 * @param int $code
	 * @param array|null $headers
	 * @throws \InvalidArgumentException For invalid status code arguments
	 */
	protected function create(string $content, int $code, array $headers = null)
	{
		return $this->responseFactory->create($content, $code, $headers);
	}

}



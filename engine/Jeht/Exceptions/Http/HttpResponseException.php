<?php
namespace Jeht\Exceptions\Http;

use Throwable;
use Jeht\Http\Response;

class HttpResponseException extends HttpException
{
	/**
	 * @var \Jeht\Http\Response
	 */
	private $response;

	/**
	 * Creates a new instance of.
	 *
	 * @param string|null $message
	 * @param \Jeht\Http\Response|null $response
	 * @param \Throwable|null $previous
	 * @param int $code = 0
	 */
	public function __construct(string $message = '', Response $response = null, \Throwable $previous = null, int $code = 0)
	{
		$headers = [];
		$statusCode = 200;
		//
		if (!is_null($response)) {
			$this->response = $response;
			//
			$headers = $response->getHeaders();
			$statusCode = $response->getStatusCode();
		}
		//
		parent::__construct($statusCode, $message, $previous, $headers, $code);
	}

	/**
	 * Returns the response instance.
	 *
	 * @return \Jeht\Http\Response|null $response
	 */
	public function getResponse()
	{
		return $this->response;
	}

}

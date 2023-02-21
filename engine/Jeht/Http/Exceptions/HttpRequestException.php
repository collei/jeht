<?php
namespace Jeht\Http\Exceptions;

use Throwable;
use Jeht\Http\Request;

class HttpRequestException extends HttpException
{
	/**
	 * @var \Jeht\Http\Request
	 */
	private $request;

	/**
	 * Creates a new instance of.
	 *
	 * @param string|null $message
	 * @param \Jeht\Http\Request|null $request
	 * @param \Throwable|null $previous
	 * @param int $code = 0
	 */
	public function __construct(string $message = '', Request $request = null, \Throwable $previous = null, int $code = 0)
	{
		$headers = [];
		//
		if (!is_null($request)) {
			$this->request = $request;
			//
			$headers = $request->getHeaders();
		}
		//
		parent::__construct(599, $message, $previous, $headers, $code);
	}

	/**
	 * Returns the request instance.
	 *
	 * @return \Jeht\Http\Request|null $request
	 */
	public function getRequest()
	{
		return $this->request;
	}

}

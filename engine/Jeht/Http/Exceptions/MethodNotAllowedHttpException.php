<?php
namespace Jeht\Http\Exceptions;

use Throwable;

class MethodNotAllowedHttpException extends HttpRequestException
{
	/**
	 * @var array
	 */
	protected $others;

	/**
	 * Creates a new instance of.
	 *
	 * @param array $others = []
	 * @param string|null $message
	 * @param \Throwable|null $previous
	 * @param array $headers = []
	 * @param int $code = 0
	 */
	public function __construct(array $others = [], string $message = null, \Throwable $previous = null, array $headers = [], int $code = 0)
	{
		parent::__construct(
			405,
			$message ?? "HTTP 405 Method not allowed.",
			$previous,
			$headers,
			$code 
		);
		//
		$this->others = $others;
	}
}


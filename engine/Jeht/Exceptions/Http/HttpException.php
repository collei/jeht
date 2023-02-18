<?php
namespace Jeht\Exceptions\Http;

use RuntimeException;

/**
 * HttpException.
 *
 * @author Kris Wallsmith <kris@symfony.com>
 *
 * Adapted from a file that is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 *-----------------------------------------------------------------------------------*
 *	Copyright (c) 2004-present Fabien Potencier
 *
 *	Permission is hereby granted, free of charge, to any person obtaining a copy
 *	of this software and associated documentation files (the "Software"), to deal
 *	in the Software without restriction, including without limitation the rights
 *	to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *	copies of the Software, and to permit persons to whom the Software is furnished
 *	to do so, subject to the following conditions:
 *
 *	The above copyright notice and this permission notice shall be included in all
 *	copies or substantial portions of the Software.
 *
 *	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *	IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *	AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *	OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *	THE SOFTWARE.
 *-----------------------------------------------------------------------------------*
 *
 * Adapted from Symfony's Symfony\Component\HttpKernel\Exception\HttpException
 * @link https://github.com/symfony/symfony/blob/6.3/src/Symfony/Component/HttpKernel/Exception/HttpException.php
 *
 */
class HttpException extends RuntimeException implements HttpExceptionInterface
{
	/**
	 * @var int
	 */
	private int $statusCode;

	/**
	 * @var array
	 */
	private array $headers;

	/**
	 * Creates a new instance of.
	 *
	 * @param int $statusCode
	 * @param string|null $message
	 * @param \Throwable|null $previous
	 * @param array $headers = []
	 * @param int $code = 0
	 */
	public function __construct(int $statusCode, string $message = '', \Throwable $previous = null, array $headers = [], int $code = 0)
	{
		$this->statusCode = $statusCode;
		$this->headers = $headers;

		parent::__construct($message, $code, $previous);
	}

	/**
	 * Returns the status code
	 *
	 * @return int
	 */
	public function getStatusCode(): int
	{
		return $this->statusCode;
	}

	/**
	 * Returns the headers
	 *
	 * @return array
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}

	/**
	 * Sets the headers
	 *
	 * @return void
	 */
	public function setHeaders(array $headers)
	{
		$this->headers = $headers;
	}
}



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
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Adapted from Symfony's Symfony\Component\HttpKernel\Exception\HttpException
 * @link https://github.com/symfony/symfony/blob/6.3/src/Symfony/Component/HttpKernel/Exception/HttpException.php
 *
 */
class HttpException extends RuntimeException implements HttpExceptionInterface
{
	private int $statusCode;
	private array $headers;

	public function __construct(int $statusCode, string $message = '', \Throwable $previous = null, array $headers = [], int $code = 0)
	{
		$this->statusCode = $statusCode;
		$this->headers = $headers;

		parent::__construct($message, $code, $previous);
	}

	public function getStatusCode(): int
	{
		return $this->statusCode;
	}

	public function getHeaders(): array
	{
		return $this->headers;
	}

	public function setHeaders(array $headers)
	{
		$this->headers = $headers;
	}
}



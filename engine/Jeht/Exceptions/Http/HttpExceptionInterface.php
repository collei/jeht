<?php
namespace Jeht\Exceptions\Http;

use Throwable;

/**
 * Interface for HTTP error exceptions.
 *
 * @author Kris Wallsmith <kris@symfony.com>
 *
 * Adapted from a file that is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Adapted from Symfony's Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
 * @link https://github.com/symfony/symfony/blob/6.3/src/Symfony/Component/HttpKernel/Exception/HttpExceptionInterface.php
 *
 */
interface HttpExceptionInterface extends Throwable
{
	/**
	 * Returns the status code.
	 */
	public function getStatusCode(): int;

	/**
	 * Returns response headers.
	 */
	public function getHeaders(): array;
}


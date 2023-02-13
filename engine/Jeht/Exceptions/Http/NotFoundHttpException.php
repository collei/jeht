<?php
namespace Jeht\Exceptions\Http;

use Throwable;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * Adapted from a file that is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Adapted from Symfony's Symfony\Component\HttpKernel\Exception\NotFoundHttpException
 * @link https://github.com/symfony/symfony/blob/6.3/src/Symfony/Component/HttpKernel/Exception/NotFoundHttpException.php
 *
 */
class NotFoundHttpException extends HttpException
{
	public function __construct(string $message = '', Throwable $previous = null, int $code = 0, array $headers = [])
	{
		parent::__construct(404, $message, $previous, $headers, $code);
	}
}


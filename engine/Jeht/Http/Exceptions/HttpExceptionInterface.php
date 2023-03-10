<?php
namespace Jeht\Http\Exceptions;

use Throwable;

/**
 * Interface for HTTP error exceptions.
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


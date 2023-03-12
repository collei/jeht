<?php
namespace Jeht\Error\Error;

use Error;
use ReflectionProperty;

/*
 * This file is part of the Symfony package.
 *
 * Copyright (c) 2019-present Fabien Potencier <fabien@symfony.com>
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
class FatalError extends Error
{
	private array $error;

	/**
	 * @param array $error An array as returned by error_get_last()
	 */
	public function __construct(
		string $message,
		int $code,
		array $error,
		int $traceOffset = null,
		bool $traceArgs = true,
		array $trace = null
	) {
		parent::__construct($message, $code);

		$this->error = $error;

		if (null !== $trace) {
			if (!$traceArgs) {
				foreach ($trace as &$frame) {
					unset($frame['args'], $frame['this'], $frame);
				}
			}
		} elseif (null !== $traceOffset) {
			if (\function_exists('xdebug_get_function_stack') && $trace = @xdebug_get_function_stack()) {
				if (0 < $traceOffset) {
					array_splice($trace, -$traceOffset);
				}
				//
				foreach ($trace as &$frame) {
					if (!isset($frame['type'])) {
						// XDebug pre 2.1.1 doesn't currently set the
						// call type key http://bugs.xdebug.org/view.php?id=695
						if (isset($frame['class'])) {
							$frame['type'] = '::';
						}
					} elseif ('dynamic' === $frame['type']) {
						$frame['type'] = '->';
					} elseif ('static' === $frame['type']) {
						$frame['type'] = '::';
					}
					//
					// XDebug also has a different name for the parameters array
					if (!$traceArgs) {
						unset($frame['params'], $frame['args']);
					} elseif (isset($frame['params']) && !isset($frame['args'])) {
						$frame['args'] = $frame['params'];
						unset($frame['params']);
					}
				}
				//
				unset($frame);
				$trace = array_reverse($trace);
			} else {
				$trace = [];
			}
		}

		foreach ([
			'file' => $error['file'],
			'line' => $error['line'],
			'trace' => $trace,
		] as $property => $value) {
			if (null !== $value) {
				$refl = new \ReflectionProperty(Error::class, $property);
				$refl->setValue($this, $value);
			}
		}
	}

	public function getError(): array
	{
		return $this->error;
	}
}


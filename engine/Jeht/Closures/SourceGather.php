<?php
namespace Jeht\Closures;

use ReflectionFunction;

/**
 * Gather the source of a closure - even with nested inside
 *
 * @author Deji <https://stackoverflow.com/users/1202953/deji>
 * @link https://stackoverflow.com/a/61357187
 * @link https://stackoverflow.com/questions/7026690/reconstruct-get-source-code-of-a-php-function
 * @since 2020-04-22 (posted by Deji at https://stackoverflow.com/questions/7026690/reconstruct-get-source-code-of-a-php-function)
 * @since 2023-02-18 (adapted from source)
 *
 * Usage:
 *
 *	$fn = [fn() => fn() => $i = 0, function () { return 1; }];
 *	$tokens = Source::readFunctionTokens(new \ReflectionFunction($fn[1]), 1);
 *
 * 0 as the second arg will return the code for the first closure in the outermost scope,
 * and 1 will return the second closure in the outermost scope.
 *
 */
final class SourceGather
{
	/**
	 * @var array
	 */
	private const OPEN_NEST_CHARS = ['(', '[', '{'];

	/**
	 * @var array
	 */
	private const CLOSE_NEST_CHARS = [')', ']', '}'];

	/**
	 * @var array
	 */
	private const END_EXPRESSION_CHARS = [';', ','];

	public static function doesCharBeginNest($char)
	{
		return \in_array($char, self::OPEN_NEST_CHARS);
	}

	public static function doesCharEndExpression($char)
	{
		return \in_array($char, self::END_EXPRESSION_CHARS);
	}

	public static function doesCharEndNest($char)
	{
		return \in_array($char, self::CLOSE_NEST_CHARS);
	}

	public static function readFunctionTokens(ReflectionFunction $fn, int $index = 0): array
	{
		$file = \file($fn->getFileName());
		$tokens = \token_get_all(\implode('', $file));
		$functionTokens = [];
		$line = 0;

		$readFunctionExpression = function ($i, &$functionTokens) use ($tokens, &$readFunctionExpression) {
			$start = $i;
			$nest = 0;

			for (; $i < \count($tokens); ++$i) {
				$token = $tokens[$i];

				if (\is_string($token)) {
					if (self::doesCharBeginNest($token)) {
						++$nest;
					} elseif (self::doesCharEndNest($token)) {
						if ($nest === 0) {
							return $i + 1;
						}

						--$nest;
					} elseif (self::doesCharEndExpression($token)) {
						if ($nest === 0) {
							return $i + 1;
						}
					}
				} elseif ($i !== $start && ($token[0] === \T_FN || $token[0] === \T_FUNCTION)) {
					return $readFunctionExpression($i, $functionTokens);
				}

				$functionTokens[] = $token;
			}

			return $i;
		};

		for ($i = 0; $i < \count($tokens); ++$i) {
			$token = $tokens[$i];
			$line = $token[2] ?? $line;

			if ($line < $fn->getStartLine()) {
				continue;
			} elseif ($line > $fn->getEndLine()) {
				break;
			}

			if (\is_array($token)) {
				if ($token[0] === \T_FN || $token[0] === \T_FUNCTION) {
					$functionTokens = [];
					$i = $readFunctionExpression($i, $functionTokens);

					if ($index === 0) {
						break;
					}

					--$index;
				}
			}
		}

		return $functionTokens;
	}
}


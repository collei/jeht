<?php
namespace Jeht\Support;

use Closure;
use ReflectionFunction;
use SplFileObject;
use Throwable;

defined('T_FN') || define('T_FN', 10001);
defined('T_FUNCTION') || define('T_FUNCTION', 10002);
defined('T_USE') || define('T_USE', 10003);

class Closures
{
	protected const FN_DELIMITERS = [',',';',')',']','}'];

	protected const CHAR_PAR_OPEN = '(';
	protected const CHAR_PAR_CLOSE = ')';
	protected const CHAR_CURLY_OPEN = '{';
	protected const CHAR_CURLY_CLOSE = '}';
	protected const CHAR_SQUARED_OPEN = '[';
	protected const CHAR_SQUARED_CLOSE = ']';
	protected const CHAR_TAB = "\t";
	protected const CHAR_COMMA = ',';
	protected const CHAR_SEMICOLON = ';';
	protected const CHAR_SPACE = ' ';
	protected const KEYWORD_GLOBAL = 'global';

	/**
	 * Returns an instance of ReflectionFunction related to the given $closure.
	 *
	 * @param \Closure $closure
	 * @return \ReflectionFunction
	 */
	public static function getReflector(Closure $closure)
	{
		return new ReflectionFunction($closure);
	}

	/**
	 * Extracts a closure from its source file and returns it.
	 * Uses ReflectionFunction to introspect the closure location.
	 *
	 * @param \Closure $closure
	 * @return string
	 */
	public static function toString(Closure $closure)
	{
		$reflector = self::getReflector($closure);
		//
		$file = $reflector->getFileName();
		$start = $reflector->getStartLine();
		$end = $reflector->getEndLine();
		//
		if ($start === false || $end === false) {
			return '';
		}
		//
		return self::filterSource(
			self::gatherCodeFromFile($file, $start, $end), $file
		);
	}

	/**
	 * Extracts a closure from the given $source and returns it.
	 * Returns null if the extracting process fails by, e.g.,
	 * parse errors.
	 *
	 * @param string $source
	 * @param bool $throws = false
	 * @return \Closure|null
	 * @throws \Throwable for errors in the code but only if $throws = true.
	 */
	public static function fromString(string $source, bool $throws = false)
	{
		try {
			return self::runCodeAsRequire($source);
			//
		} catch (Throwable $t) {
			if (!$throws) {
				return null;
			}
			//
			throw $t;
		}
	}

	/**
	 * Filters an anonymous function from its source file, given the source.
	 *
	 * @param string $source
	 * @return string
	 */
	public static function filterSource(string $source, string $originalFile)
	{
		$source =  '<?php ' . $source . ' ?>';
		//
		$tokens = token_get_all($source, TOKEN_PARSE);
		$filtered = $uses = [];
		$curlies = $infunc = $from = 0;
		//
		// Let's define where the function begins, right at the
		// corresponding keyword
		foreach ($tokens as $key => $token) {
			if (is_array($token)) {
				// function | fn 
				if ($token[0] === T_FUNCTION || $token[0] === T_FN) {
					$infunc = $token[0];
					$from = $key;
					break;
				}
			}
		}
		//
		// defined with keyword function
		// it may posses a use clause
		if ($infunc === T_FUNCTION) {
			$in_use = false;
			$has_uses_clause = false;
			//
			foreach ($tokens as $key => $token) {
				//
				// define the limits of the function
				if (self::CHAR_CURLY_OPEN === $token) {
					++$curlies;
				} elseif (self::CHAR_CURLY_CLOSE === $token) {
					--$curlies;
					//
					if (0 === $curlies) {
						$infunc = 0;
					}
				} elseif (self::CHAR_PAR_CLOSE === $token) {
					if ($in_use) {
						$in_use = false;
					}
				}
				//
				// detects if is there any use clause
				// in order to act properly
				if (is_array($token)) {
					$token[] = token_name($token[0]);
					//
					if ($token[0] === T_USE) {
						$in_use = true;
						$has_uses_clause = true;
					}
					//
					if ($token[0] === T_VARIABLE && $in_use) {
						$uses[] = $token[1];
					}
				}
				//
				// let's convert the closure's use clause
				// into a global clause inside the function 
				if ($key >= $from) {
					if ($curlies > 0 || $infunc !== 0) {
						// add a token
						$filtered[] = $token;
						//
						// here we generate the global clause inside the function
						// so we'll make ourselves free of any erros because of
						// undefined variables 
						if ($curlies > 0 && !empty($uses) && !$in_use) {
							$filtered[] = PHP_EOL;
							$filtered[] = self::CHAR_TAB;
							$filtered[] = self::KEYWORD_GLOBAL;
							foreach ($uses as $use) {
								$filtered[] = self::CHAR_SPACE;
								$filtered[] = $use;
								$filtered[] = self::CHAR_COMMA;
							}
							array_pop($filtered);
							$filtered[] = self::CHAR_SEMICOLON;
							$filtered[] = PHP_EOL;
							$filtered[] = self::CHAR_TAB;
							unset($uses);
						}
					} elseif ($curlies === 0 && $token === self::CHAR_CURLY_CLOSE) {
						// defines the end of token collection
						// and exits the loop
						$filtered[] = $token;
						break;
					}
				}
			}
			//
			// only if a use clause was defined
			// for the current closure
			if ($has_uses_clause) {
				$use_start = 0;
				$use_end = 0;
				//
				// define the start and end of the use clause
				foreach ($filtered as $key => $token) {
					if (is_array($token) && $token[0] === T_USE) {
						$use_start = $key;
					} elseif ($use_start > 0 && $token === self::CHAR_PAR_CLOSE) {
						$use_end = $key;
						break;
					}
				}
				//
				// remove the use clause from the token list
				array_splice($filtered, $use_start, $use_end - $use_start + 1, []);
			}
			//
		} elseif ($infunc === T_FN) {
			// that's an arrow function !
			// @todo work on how define that inner variables
			// that not got declared as parameters
			foreach ($tokens as $key => $token) {
				if (self::CHAR_PAR_OPEN === $token) {
					++$curlies;
				} elseif (self::CHAR_PAR_CLOSE === $token) {
					--$curlies;
				}
				//
				if ($curlies === 0 && in_array($token, self::FN_DELIMITERS)) {
					$infunc = 0;
				}
				//
				if (($key >= $from) && ($infunc !== 0)) {
					$filtered[] = $token;
				}
			}
		}
		//
		// this line includes the original file in order to give
		// a context to our serialized closure when it gets converted back
		// to a lively closure ready to use. However, the early context
		// could not yet made available.
		$result = ' include_once \'' . $originalFile . '\';' . PHP_EOL . 'return '; 
		//
		foreach ($filtered as $ftok) {
			$result .= is_array($ftok) ? $ftok[1] : $ftok;
		}
		//
		return $result;
	}

	/**
	 * Extracts code from its source file, given start and end line.
	 *
	 * @param string $fileName
	 * @param int $start
	 * @param int $end
	 * @return string
	 */
	protected static function gatherCodeFromFile(string $fileName, int $start, int $end)
	{
		$file = new SplFileObject($fileName);
		$line = 1;
		$lines = [];
		//
		while (!$file->eof()) {
			// line by line, trimming at right
			$lineString = rtrim($file->fgets());
			//
			if ($line >= $start) {
				$lines[] = $lineString;
			}
			//
			++$line;
			//
			if ($line > $end) {
				// stop! We have all lines we need
				break;
			}
		}
		//
		// closes the file handle
		$file = null;
		//
		// as a plain string with line delimiters
		return implode(PHP_EOL, $lines);
	}

	/**
	 * Executes the given code into a temporary require file and
	 * return a result value, if any.
	 *
	 * @param string $source
	 * @return mixed
	 * @throws \Throwable for errors caused by the code.
	 */
	protected static function runCodeAsRequire(string $source)
	{
		$tempFile = tempnam(sys_get_temp_dir(), 'Clos');
		$code = '<?php' . PHP_EOL . $source . '; ';
		//
		file_put_contents($tempFile, $code);
		//
		$result = require $tempFile;
		//
		//unlink($tempFile);
		//
		return $result;
	} 

}



<?php
namespace Jeht\Support\Env;

use Jeht\Support\Env\Interfaces\Parser as EnvParserInterface;
use Jeht\Support\Str;

class Parser implements EnvParserInterface
{
	/**
	 * @var string
	 *
	 * the 'strange' subpattern group ($|...) helps to gather empty variable as as MYVAR=
	 *
	 */
	protected const ENTRY_PATTERN = '/^\s*(\w+)\s*=($|\s*[\$!]?(\'((?:\\\\.|[^\'])*)\'|"((?:\\\\.|[^"])*)"|[^#\n]*).*)/m';

	/**
	 * @var string
	 */
	protected const ENTRY_EXPANDER = '/(\\${(\\w+)})/';

	/**
	 * @var array
	 */
	protected $parsedEntries = [];

	/**
	 * @var string
	 */
	protected $source;

	/**
	 * Does the source parsing into discrete variable units
	 *
	 * @return void
	 */
	protected function parseSource()
	{
		if (false !== preg_match_all(self::ENTRY_PATTERN, $this->source, $matches, PREG_SET_ORDER)) {
			$this->parsedEntries = [];
			//
			foreach ($matches as $match) {
				list($raw, $name, $rawValue) = $match;
				//
				$value = $match[3] ?? $rawValue;
				//
				$this->parsedEntries[$name] = $this->parseValue($value, $rawValue);
			}
		}
	}

	/**
	 * Does the value parsing on special characters \r, \t, \n, \0
	 *
	 * @param string $value
	 * @param string|null $rawValue
	 * @return mixed
	 */
	protected function parseValue(string $value, string $rawValue = null)
	{
		$value = trim($value);
		$flag = substr($rawValue ?? '', 0, 1);
		//
		if (Str::isDoubleQuoted($value)) {
			$value = str_replace(
				['\r','\n','\t','\0'],
				["\r","\n","\t","\0"],
				$value
			);
			//
			$value = $this->expandVariables($value);
		} elseif ('$' === $flag || '!' === $flag) {
			$value = $this->expandVariables($value);
		}
		//
		return $value;
	}

	/**
	 * Does the variable expansion
	 *
	 * @param string $value
	 * @return string
	 */
	protected function expandVariables(string $value)
	{
		if (false !== preg_match_all(self::ENTRY_EXPANDER, $value, $matches, PREG_SET_ORDER)) {
			$withExpanded = $value;
			//
			foreach ($matches as $match) {
				$raw = $match[0];
				$var = $match[2];
				$value = $match[3] ?? $var;
				//
				$result = $this->parsedEntries[$var] ?? $raw;
				//
				$withExpanded = Str::unquote(str_replace($raw, $result, $withExpanded));
			}
			//
			return $withExpanded;
		}
		//
		return $value;
	}

	/**
	 * Initializes a new instance of the parser
	 *
	 * @param string|null $source
	 */
	public function __construct(string $source = null)
	{
		$this->source = $source ?? '';
		//
		return $this;
	}

	/**
	 * Creates a new parser and loads the specified string as source.
	 *
	 * @static
	 * @param string $source
	 * @return static
	 */
	public static function from(string $source)
	{
		return (new static())->setSource($source);
	}

	/**
	 * Sets the source to be parsed.
	 *
	 * @param string $source
	 * @return this
	 */
	public function setSource(string $source)
	{
		$this->source = $source;
		//
		return $this;
	}

	/**
	 * Returns the raw source.
	 *
	 * @return string
	 */
	public function getSource()
	{
		return $this->source;
	}

	/**
	 * Returns the parsed entries as array.
	 *
	 * @return array
	 */
	public function getEntries()
	{
		return $entries = $this->parsedEntries;
	}

	/**
	 * Starts the parsing
	 *
	 * @return this
	 */
	public function parse()
	{
		$this->parseSource();
		//
		return $this;
	}

}

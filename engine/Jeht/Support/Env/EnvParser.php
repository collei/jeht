<?php
namespace Jeht\Support\Env;

use Jeht\Support\Str;

class EnvParser
{
	protected const ENTRY_PATTERN = '/^\\s*(\\w+)\\s*=\\s*(\\\'[^\']*\\\'|\\"[^"]*\\"|[^#\\n]*)/m';

	protected const ENTRY_EXPANDER = '/(\\${(\\w+)})/';

	protected $parsedEntries = [];

	protected $source;

	protected function parseSource()
	{
		if (false !== preg_match_all(self::ENTRY_PATTERN, $this->source, $matches, PREG_SET_ORDER)) {
			$this->parsedEntries = [];
			//
			foreach ($matches as $match) {
				list($raw, $name, $value) = $match;
				//
				$this->parsedEntries[$name] = $this->parseValue($value);
			}
		}
	}

	protected function parseValue(string $value)
	{
		$value = trim($value);
		//
		if (Str::isDoubleQuoted($value)) {
			$value = str_replace(
				['\r','\n','\t','\0'],
				["\r","\n","\t","\0"],
				$value
			);
			//
			$value = $this->expandVariables($value);
		}
		//
		return $value;
	}

	protected function expandVariables(string $value)
	{
		if (false !== preg_match_all(self::ENTRY_EXPANDER, $value, $matches, PREG_SET_ORDER)) {
			$withExpanded = $value;
			//
			foreach ($matches as $match) {
				list($raw, $var) = $match;
				//
				$result = $this->parsedEntries[$var] ?? $raw;
				//
				$withExpanded = str_replace($raw, $result, $withExpanded);
			}
			//
			return $withExpanded;
		}
		//
		return $value;
	}

	public function __construct(string $source = null)
	{
		$this->source = $source ?? '';
		//
		return $this;
	}

	public function parse()
	{
		$this->parseSource();
		//
		return $this;
	}

	public function fromFile(string $path)
	{
		if (is_readable($path)) {
			$this->source = file_get_contents($path);
		}
		//
		return $this;
	}



}

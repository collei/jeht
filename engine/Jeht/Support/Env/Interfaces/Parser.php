<?php
namespace Jeht\Support\Env\Interfaces;

interface Parser
{
	/**
	 * Sets the source to be parsed.
	 *
	 * @param string $source
	 * @return this
	 */
	public function setSource(string $source);

	/**
	 * Returns the raw source.
	 *
	 * @return string
	 */
	public function getSource();

	/**
	 * Returns the parsed entries as array.
	 *
	 * @return array
	 */
	public function getEntries();

	/**
	 * Starts the parsing
	 *
	 * @return this
	 */
	public function parse();
}


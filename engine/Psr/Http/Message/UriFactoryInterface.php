<?php
namespace Psr\Http\Message;

use Psr\Http\Message\UriInterface;

interface UriFactoryInterface
{
	/**
	 * Create a new URI.
	 *
	 * @param string $uri The URI to parse.
	 *
	 * @throws \InvalidArgumentException If the given URI cannot be parsed.
	 */
	public function createUri(string $uri = '') : UriInterface;
}



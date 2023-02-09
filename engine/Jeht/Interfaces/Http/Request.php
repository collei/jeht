<?php
namespace Jeht\Interfaces\Http;

use Psr\Http\Message\ServerRequestInterface;

interface Request extends ServerRequestInterface
{
	/**
	 * Returns if the specified request attribute exists.
	 *
	 * @see getAttributes()
	 * @param string $name The attribute name.
	 * @return bool
	 */
	public function hasAttribute($name);
	
}

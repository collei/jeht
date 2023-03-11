<?php
namespace Jeht\Support\Interfaces;

interface Htmlable
{
	/**
	 * Get content as a string of HTML.
	 *
	 * @return string
	 */
	public function toHtml();
}


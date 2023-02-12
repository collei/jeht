<?php
namespace Jeht\Support\Streams;

class StringStream extends Stream
{
	/**
	 * Creates a resouce from string
	 *
	 * @param string $content
	 * @return resource
	 */
	private function resouceFromString(string $content = '')
	{
		$handle = fopen('php://memory', 'w+');
		//
		fwrite($handle, $content);
		//
		return $handle;
	}
	
	/**
	 * Builds a StringStream from a raw string
	 *
	 * @param string $content
	 * @param array $metadata
	 */
	public function __construct(string $content, array $metadata = [])
	{
		parent::__construct(
			$this->resouceFromString($content), strlen($content), $metadata
		);
	}
}

<?php
namespace Jeht\Cache;

use Exception;
use Psr\SimpleCache\CacheException as CacheExceptionInterface;

/**
 * Exception interface for all exceptions thrown by an Implementing Library.
 */
class CacheException extends Exception implements CacheExceptionInterface
{
	//
}

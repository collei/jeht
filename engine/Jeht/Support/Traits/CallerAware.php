<?php
namespace Jeht\Support\Traits;

trait CallerAware
{
	/**
	 * Retrieves the name of the caller function
	 *
	 * @return string
	 */
	protected function getCallerFunctionName()
	{
		return debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,3)[2]['function'];
	}

	/**
	 * Retrieves the name of the caller's namespaced class name
	 *
	 * @return string
	 */
	protected function getCallerClassName()
	{
		return debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,3)[2]['class'];
	}

	/**
	 * Retrieves the name of the caller's script file name (full path)
	 *
	 * @return string
	 */
	protected function getCallerFileName()
	{
		return debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,3)[2]['file'];
	}

}
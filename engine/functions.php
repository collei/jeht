<?php

/**
 *	The following functions help to provide support to some PHP functions
 *	that weren't available before PHP 8.
 *
 */

/**
 * Check if $haystack starts with $needle.
 *
 * @param string $haystack
 * @param string $needle
 * @param bool
 *
 * source: Laravel Framework
 * @link https://github.com/laravel/framework/blob/8.x/src/Illuminate/Support/Str.php
 */
if (!function_exists('str_starts_with')) {
	function str_starts_with($haystack, $needle)
	{
		return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
	}
}

/**
 * Check if $haystack ends with $needle.
 *
 * @param string $haystack
 * @param string $needle
 * @param bool
 *
 * source: Laravel Framework 
 * @link https://github.com/laravel/framework/blob/8.x/src/Illuminate/Support/Str.php
 */
if (!function_exists('str_ends_with')) {
	function str_ends_with($haystack, $needle)
	{
		return $needle !== '' && substr($haystack, -strlen($needle)) === (string)$needle;
	}
}

/**
 * Check if $haystack contains $needle.
 *
 * @param string $haystack
 * @param string $needle
 * @param bool
 *
 * source: Laravel Framework
 * @link https://github.com/laravel/framework/blob/8.x/src/Illuminate/Support/Str.php
 */
if (!function_exists('str_contains')) {
	function str_contains($haystack, $needle)
	{
		return $needle !== '' && mb_strpos($haystack, $needle) !== false;
	}
}

/**
 * Returns resource ID.
 *
 * @param resource $resource
 * @param int
 */
if (!function_exists('get_resource_id')) {
	function get_resource_id($resource)
	{
		return (int) $resource;
	}
}


///
///	dd() and du() allows debug tools for the developper
///	without depend on the Jeht helper loading engine.
///

/**
 * Dumps things and stops execution immediately.
 *
 * @param mixed ...$info
 * @return never
 */
if (!function_exists('dd')) {
	function dd(...$info)
	{
		$dt = debug_backtrace(2,2)[1] ?? debug_backtrace(2,2)[0];
		//
		$file = $dt['file'] ?? '(none)';
		$line = $dt['line'] ?? '(none)';
		$method = isset($dt['class'])
			? ($dt['class'] . ($dt['type'] ?? '-:') . $dt['function'])
			: $dt['function'];
		//
		$dumpit = '<div><b>dd</b>'
			. " (<code>$file</code>, <code>$line</code>, <code>$method</code>): <pre>"
			. print_r($info,true)
			. '</pre></div>';
		//
		die($dumpit);
	}
}

/**
 * Dumps things without stopping execution.
 *
 * @param mixed ...$info
 * @return void
 */
if (!function_exists('du')) {
	function du(...$info)
	{
		static $cha = 0;
		++$cha;
		$dt = debug_backtrace(2,2)[1] ?? debug_backtrace(2,2)[0];
		//
		$file = $dt['file'] ?? '(none)';
		$line = $dt['line'] ?? '(none)';
		$method = isset($dt['class'])
			? ($dt['class'] . ($dt['type'] ?? '-:') . $dt['function'])
			: $dt['function'];
		//
		$dumpit = "<div><b>du</b> [<i>$cha</i>]"
			. " (<code>$file</code>, <code>$line</code>, <code>$method</code>): <pre>"
			. print_r($info,true)
			. '</pre></div>';
		//
		echo ($dumpit);
	}
}






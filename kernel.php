<?php
include __DIR__ . '/vendor/autoload.php';

function dd(...$info)
{
	$dt = debug_backtrace(2,2)[1];
	$arquivo = $dt['file'];
	$linha = $dt['line'];
	$metodo = isset($dt['class']) ? ($dt['class'] . ($dt['type'] ?? '-:') . $dt['function']) : $dt['function'];
	die("<div><b>dd</b> (<code>$arquivo</code>, <code>$linha</code>, <code>$metodo</code>): <pre>".print_r($info,true).'</pre></div>');
}

function du(...$info)
{
	static $cha = 0;
	++$cha;
	$dt = debug_backtrace(2,2)[1];
	$arquivo = $dt['file'];
	$linha = $dt['line'];
	$metodo = isset($dt['class']) ? ($dt['class'] . ($dt['type'] ?? '-:') . $dt['function']) : $dt['function'];
	echo("<div><b>du</b> [<i>$cha</i>] (<code>$arquivo</code>, <code>$linha</code>, <code>$metodo</code>): <pre>".print_r($info,true).'</pre></div>');
}

function init(string $baseDir)
{
	$app = new Jeht\Ground\Application($baseDir);
	//
	$app->singleton(Jeht\Interfaces\Http\Kernel::class, App\Http\Kernel::class);
	//
	return $app;
}


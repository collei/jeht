<?php
include __DIR__ . '/vendor/autoload.php';

function dd($arquivo = null, $linha = 0, $metodo = null, ...$info)
{
	die("<div>dd: <br><pre>".print_r(compact('arquivo','linha','metodo','info'),true).'</pre></div>');
}

function du($arquivo = null, $linha = 0, $metodo = null, ...$info)
{
	static $cha = 0;
	++$cha;
	echo("<div>du($cha): <br><pre>".print_r(compact('arquivo','linha','metodo','info'),true).'</pre></div>');
}

function init(string $baseDir)
{
	$app = new Jeht\Ground\Application($baseDir);
	//
	$app->singleton(Jeht\Interfaces\Http\Kernel::class, App\Http\Kernel::class);
	//
	return $app;
}


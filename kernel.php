<?php
include __DIR__ . '/vendor/autoload.php';

function init(string $baseDir)
{
	$app = new Jeht\Ground\Application($baseDir);
	//

	$app->singleton(Jeht\Interfaces\Ground\Application::class, Jeht\Ground\Application::class);
	$app->singleton(Jeht\Interfaces\Http\Kernel::class, App\Http\Kernel::class);
	//
	return $app;
}


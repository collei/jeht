<?php
include __DIR__ . '/vendor/autoload.php';

use Jeht\Ground\Application;
use Jeht\Routing\Router;
use Jeht\Database\DB;

function init(string $appName, string $baseDir)
{
	return new Application($appName, $baseDir);
}


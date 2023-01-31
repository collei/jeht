<?php
include __DIR__ . '/../vendor/autoload.php';

use Ground\Kernel\Web\Application;
use Ground\Http\Routing\Router;
use Ground\Database\DB;

function init(string $appName, string $baseDir)
{
	return new Application($appName, $baseDir);
}


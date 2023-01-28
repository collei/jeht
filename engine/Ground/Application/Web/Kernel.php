<?php
namespace Ground\Application\Web;

use Ground\Http\Routing\Router;
use Ground\Http\Request\HttpRequest;

class Kernel
{
	protected $name;
	protected $baseDir;
	protected $router;
	protected $bindings = [];
	protected $singletons = [];

	public function __construct(string $name, string $baseDir, Router $router)
	{
		$this->name = $name;
		$this->baseDir = $baseDir;
		$this->router = $router;
	}

	public function start(HttpRequest $input)
	{

	}

	public function run()
	{
		
	}

	/*----------------------*\
		static helpers
	\*----------------------*/


	public static function initialize(string $name, string $baseDir)
	{
		return new static($name, $baseDir, Router::generateHandler());
	}
	
}


<?php
namespace Jeht\Ground\Loaders;

class Autoloader
{
	private $previouslyLoaded = [];

	private $namespace;
	private $rootPath;

	private static $instance = null;

	protected function addLoaded(string $class, string $file)
	{
		$this->previouslyLoaded[$class] = $file;
	}

	protected function loadedExists(string $class)
	{
		if (\array_key_exists($class, $this->previouslyLoaded)) {
			return $this->previouslyLoaded[$class];
		}
		//
		return false;
	}

	protected function autoloadRegister()
	{
		$self = $this;
		//
		\spl_autoload_register(function ($class) use ($self){
			if ($file = $self->loadedExists($class)) {
				require_once $file;
				return;
			}
			//
			$file = $self->rootPath . DIRECTORY_SEPARATOR
				. \str_replace('\\', DIRECTORY_SEPARATOR, $class)
				. '.php';

			echo "<div>app autoloader: tried reap class <b>$class</b> from <b>$file</b></div>";

			//
			if (\file_exists($file)) {
				$self->addLoaded($class, $file);
				require_once $file;
			}
		});
	}

	public function __construct(string $namespace, string $rootPath)
	{
		$this->namespace = $namespace;
		$this->rootPath = $rootPath;
		//
		$this->autoloadRegister();
	}

	public static function register(string $namespace, string $rootPath)
	{
		return (self::$instance = new self($namespace, $rootPath));
	}
	
}


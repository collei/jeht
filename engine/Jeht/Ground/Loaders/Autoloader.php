<?php
namespace Jeht\Ground\Loaders;

class Autoloader
{
	private $previouslyLoaded = [];

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
				require $file;
				return true;
			}
			//
			$file = \str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
			if (\file_exists($file)) {
				$self->addLoaded($class, $file);
				require $file;
				return true;
			}
			return false;
		});
	}

	public function __construct()
	{
		$this->autoloadRegister();
	}

	public static function register()
	{
		return (self::$instance = new self());
	}
	
}


<?php
namespace Jeht\Support\Traits;

use Jeht\Support\Facades\App;

trait ConfigAware
{
	/**
	 * @var \Jeht\Config\Repository
	 */
	private $configRepository;

	/**
	 * Returns a configuration setting.
	 *
	 * @param	string	$key
	 * @param	mixed	$default
	 * @return	mixed
	 */
	private function getRepository()
	{
		if ($this->configRepository) {
			return $this->configRepository;
		}
		//
		$this->configRepository = App::getConfigRepository();
	}

	/**
	 * Returns a configuration setting.
	 *
	 * @param	string	$key
	 * @param	mixed	$default
	 * @return	mixed
	 */
	protected function config(string $key, $default)
	{
		return $this->getRepository()->get($key, $default);
	} 
}

<?php
namespace Jeht\Encryption;

use Jeht\Support\ServiceProvider;
use Jeht\Support\Str;
use Jeht\Support\Traits\Tappable;
use Laravel\SerializableClosure\SerializableClosure;

/**
 * Adapted from the works at illuminate/encryption package 
 * @author Taylor Otwell <taylor@laravel.com>.
 *
 * @link https://github.com/illuminate/encryption
 * @link https://github.com/illuminate/encryption/blob/master/EncryptionServiceProvider.php
 * @link https://github.com/illuminate/encryption/blob/master/LICENSE.md
 *
 */
class EncryptionServiceProvider extends ServiceProvider
{
	use Tappable;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerEncrypter();
		$this->registerSerializableClosureSecurityKey();
	}

	/**
	 * Register the encrypter.
	 *
	 * @return void
	 */
	protected function registerEncrypter()
	{
		$this->app->singleton('encrypter', function ($app) {
			$config = $app->make('config')->get('app');

			return new Encrypter($this->parseKey($config), $config['cipher']);
		});
	}

	/**
	 * Configure Serializable Closure signing for security.
	 *
	 * @return void
	 */
	protected function registerSerializableClosureSecurityKey()
	{
		$config = $this->app->make('config')->get('app');

		if (! class_exists(SerializableClosure::class) || empty($config['key'])) {
			return;
		}

		SerializableClosure::setSecretKey($this->parseKey($config));
	}

	/**
	 * Parse the encryption key.
	 *
	 * @param  array  $config
	 * @return string
	 */
	protected function parseKey(array $config)
	{
		if (Str::startsWith($key = $this->key($config), $prefix = 'base64:')) {
			$key = base64_decode(Str::after($key, $prefix));
		}

		return $key;
	}

	/**
	 * Extract the encryption key from the given configuration.
	 *
	 * @param  array  $config
	 * @return string
	 *
	 * @throws \Jeht\Encryption\MissingAppKeyException
	 */
	protected function key(array $config)
	{
		return $this->tapValue($config['key'], function ($key) {
			if (empty($key)) {
				throw new MissingAppKeyException;
			}
		});
	}
}


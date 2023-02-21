<?php
namespace Jeht\Encryption\Interfaces;

/**
 * Adapted from the works at illuminate/contracts package
 * in addition to illuminate/encryption package
 * @author Taylor Otwell <taylor@laravel.com>.
 *
 * @link https://github.com/illuminate/contracts
 * @link https://github.com/illuminate/contracts/blob/master/Encryption/Encrypter.php
 * @link https://github.com/illuminate/contracts/blob/master/LICENSE.md
 *
 */
interface EncrypterInterface
{
	/**
	 * Encrypt the given value.
	 *
	 * @param  mixed  $value
	 * @param  bool  $serialize
	 * @return string
	 *
	 * @throws \Jeht\Encryption\EncryptException
	 */
	public function encrypt($value, bool $serialize = true);

	/**
	 * Decrypt the given value.
	 *
	 * @param  string  $payload
	 * @param  bool  $unserialize
	 * @return mixed
	 *
	 * @throws \Jeht\Encryption\DecryptException
	 */
	public function decrypt(string $payload, bool $unserialize = true);

	/**
	 * Get the encryption key that the encrypter is currently using.
	 *
	 * @return string
	 */
	public function getKey();
}


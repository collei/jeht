<?php
namespace Jeht\Encryption\Interfaces;

/**
 * Adapted from the works at illuminate/contracts package
 * in addition to illuminate/encryption package
 * @author Taylor Otwell <taylor@laravel.com>.
 *
 * @link https://github.com/illuminate/contracts
 * @link https://github.com/illuminate/contracts/blob/master/Encryption/StringEncrypter.php
 * @link https://github.com/illuminate/contracts/blob/master/LICENSE.md
 *
 */
interface StringEncrypterInterface
{
	/**
	 * Encrypt a string without serialization.
	 *
	 * @param  string  $value
	 * @return string
	 *
	 * @throws \Jeht\Encryption\EncryptException
	 */
	public function encryptString(string $value);

	/**
	 * Decrypt the given string without unserialization.
	 *
	 * @param  string  $payload
	 * @return string
	 *
	 * @throws \Jeht\Encryption\DecryptException
	 */
	public function decryptString(string $payload);
}

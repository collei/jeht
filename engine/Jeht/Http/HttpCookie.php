<?php
namespace Jeht\Http;

use Jeht\Interfaces\Support\Stringable;
use Jeht\Support\Traits\InteractsWithTime;
use DateTimeInterface;

/**
 *	Encapsulates a HTTP cookie
 *
 *	@author	alarido <alarido.su@gmail.com>
 *	@since	2021-05-xx
 */
class HttpCookie implements Stringable
{
	use InteractsWithTime;

	/**
	 *	@var string
	 *	@var string
	 *	@var string
	 */
	public const SAMESITE_NONE = 'None';
	public const SAMESITE_LAX = 'Lax';
	public const SAMESITE_STRICT = 'Strict';

	/**
	 *	@var array
	 */
	protected const SAMESITE_VALID = ['None', 'Lax', 'Strict'];

	/**
	 *	@var string $name
	 */
	private $name;

	/**
	 *	@var string $value
	 */
	private $value;

	/**
	 *	@var int $expires
	 */
	private $expires;

	/**
	 *	@var string $path
	 */
	private $path;

	/**
	 *	@var string $domain
	 */
	private $domain;

	/**
	 *	@var bool $secure
	 */
	private $secure;

	/**
	 *	@var bool $secureDefault
	 */
	private $secureDefault = false;

	/**
	 *	@var bool $httpOnly
	 */
	private $httpOnly;

	/**
	 *	@var string $sameSite
	 */
	private $sameSite;

	/**
	 *	@var string $raw
	 */
	private $raw;

	/**
	 *	Builds a Cookie instance
	 *
	 *	@param string $name
	 *	@param string $value
	 *	@param int $expires
	 *	@param string $path
	 *	@param string $domain
	 *	@param bool $secure
	 *	@param bool $httpOnly
	 *	@param string $sameSite
	 */
	public function __construct(
		string $name,
		string $value = '',
		int $expires = 0,
		string $path = '',
		string $domain = '',
		bool $secure = false,
		bool $httpOnly = false,
		string $sameSite = 'lax'
	) {
		$this->name = $name;
		$this->value = $value;
		$this->expires = $expires;
		$this->path = $path;
		$this->domain = $domain;
		$this->secure = $secure;
		$this->httpOnly = $httpOnly;
		//
		$sameSite = ucfirst(strtolower($sameSite));
		//
		if (in_array(strtolower($sameSite), self::SAMESITE_VALID)) {
			$this->sameSite = $sameSite;
		} else {
			$this->sameSite = 'Lax';
		}
	}

	/**
	 * Retrieves the cookie name
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Retrieves the cookie value
	 *
	 * @return string
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * Retrieves the cookie expires
	 *
	 * @return int
	 */
	public function getExpires()
	{
		return $this->expires;
	}

	/**
	 * Retrieves the cookie path
	 *
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * Retrieves the cookie domain
	 *
	 * @return string
	 */
	public function getDomain()
	{
		return $this->domain;
	}

	/**
	 * Retrieves whether the cookie is secure
	 *
	 * @return bool
	 */
	public function isSecure()
	{
		return $this->secure ?? $this->secureDefault;
	}

	/**
	 * Retrieves whether the cookie is HTTP only
	 *
	 * @return bool
	 */
	public function isHttpOnly()
	{
		return $this->httpOnly;
	}

	/**
	 * Retrieves whether the cookie behaves across sites
	 *
	 * @return string
	 */
	public function getSameSite()
	{
		return $this->sameSite;
	}

	/**
	 * Retrieves whether the cookie is secure
	 *
	 * @return void
	 */
	public function setSecureDefault(bool $default)
	{
		$this->secureDefault = $default;
	}

	/**
	 *	Outputs the cookie as HTTP header for the browser
	 *
	 *	@return	void
	 */
	public function output()
	{
		$options = [
			'expires' => $this->expires,
			'path' => $this->path,
			'domain' => $this->domain,
			'secure' => $this->secure ?? $this->secureDefault,
			'httponly' => $this->httpOnly,
			'samesite' => $this->sameSite
		];
		//
		setcookie($this->name, $this->value, $options);
	}

	/**
	 *	Performs server-side cookie removal
	 *
	 *	@return	void
	 */
	public function remove()
	{
		setcookie($this->name, '', time() - 43200);
	}

	/**
	 *	Creates a string version of the stored data.
	 *
	 *	@return	string
	 */
	public function asString()
	{
		$nibs = [$this->name => $this->value];
		//
		if (!empty($this->expires)) {
			$date = $this->addRealSecondsTo($this->now(), $this->expires);
			//
			$nibs['expires'] = $date->format(DateTimeInterface::RFC7231);
		}
		//
		if (!empty($this->domain)) {
			$nibs['domain'] = $this->domain;
		}
		//
		if (!empty($this->path)) {
			$nibs['path'] = $this->path;
		}
		//
		if ($this->secure) {
			$nibs['secure'] = true;
		}
		//
		if ($this->httpOnly) {
			$nibs['HttpOnly'] = true;
		}
		//
		if (!empty($this->sameSite)) {
			$nibs['SameSite'] = $this->sameSite;
		}
		//
		foreach ($nibs as $name => &$value) {
			$value = is_bool($value) ? $name : ($name.'='.$value);
		}
		//
		return implode('; ', $nibs);
	}

	/**
	 * Returns it as a header string (prefixed by 'Set-Cookie: ').
	 *
	 * @return string
	 */
	public function asHeaderString(): string
	{
		return 'Set-Cookie: ' . $this->asString();
	}

	/**
	 * Converts to string
	 *
	 * @return string
	 */
	public function __toString(): string
	{
		return $this->asString();
	}

}



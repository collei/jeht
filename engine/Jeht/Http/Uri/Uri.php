<?php
namespace Jeht\Http\Uri;

use Psr\Http\Message\UriInterface;

class Uri implements UriInterface
{
	protected $parts = [
		'scheme' => '',
		'user' => '',
		'pass' => '',
		'host' => '',
		'port' => 0,
		'path' => '',
		'query' => '',
		'fragment' => '',
	]

	/**
	 * Creates a new Uri instance
	 *
	 * @param string $uri
	 */
	public function __construct(string $uri)
	{
		$tokens = parse_url($uri);
		//
		$this->parts['scheme'] = $tokens['scheme'] ?? null;
		$this->parts['host'] = $tokens['host'] ?? null;
		$this->parts['port'] = $tokens['port'] ?? null;
		$this->parts['user'] = $tokens['user'] ?? null;
		$this->parts['pass'] = $tokens['pass'] ?? null;
		$this->parts['path'] = $tokens['path'] ?? null;
		$this->parts['query'] = $tokens['query'] ?? null;
		$this->parts['fragment'] = $tokens['fragment'] ?? null;
	}

	/**
	 * Retrieve the scheme component of the URI.
	 *
	 * @see https://tools.ietf.org/html/rfc3986#section-3.1
	 * @return string The URI scheme.
	 */
	public function getScheme()
	{
		return $this->parts['scheme'] ?? '';
	}

	/**
	 * Retrieve the authority component of the URI.
	 *
	 * @see https://tools.ietf.org/html/rfc3986#section-3.2
	 * @return string The URI authority, in "[user-info@]host[:port]" format.
	 */
	public function getAuthority()
	{
		$authority = '';
		//
		if (!empty($this->parts['user'])) {
			$authority .= $this->parts['user'];
			//
			if (!empty($this->parts['pass'])) {
				$authority .= ':' . $this->parts['pass'];
			}
		}
		//
		if (!empty($this->parts['host'])) {
			if (!empty($authority)) {
				$authority .= '@' . $this->parts['host'];
			} else {
				$authority .= $this->parts['host'];
			}
			//
			if (!empty($this->parts['port'])) {
				$authority .= ':' . $this->parts['port'];
			}
		}
		//
		return $authority;
	}

	/**
	 * Retrieve the user information component of the URI.
	 *
	 * @return string The URI user information, in "username[:password]" format.
	 */
	public function getUserInfo()
	{
		return $this->parts['userInfo'] ?? '';
	}

	/**
	 * Retrieve the host component of the URI.
	 *
	 * @see http://tools.ietf.org/html/rfc3986#section-3.2.2
	 * @return string The URI host.
	 */
	public function getHost()
	{
		return $this->parts['host'] ?? '';
	}

	/**
	 * Retrieve the port component of the URI.
	 *
	 * @return null|int The URI port.
	 */
	public function getPort()
	{
		return $this->parts['port'] ?? NULL;
	}

	/**
	 * Retrieve the path component of the URI.
	 *
	 * @see https://tools.ietf.org/html/rfc3986#section-2
	 * @see https://tools.ietf.org/html/rfc3986#section-3.3
	 * @return string The URI path.
	 */
	public function getPath()
	{
		return $this->parts['path'] ?? '';
	}

	/**
	 * Retrieve the query string of the URI.
	 *
	 * @see https://tools.ietf.org/html/rfc3986#section-2
	 * @see https://tools.ietf.org/html/rfc3986#section-3.4
	 * @return string The URI query string.
	 */
	public function getQuery()
	{
		return $this->parts['query'] ?? '';
	}

	/**
	 * Retrieve the fragment component of the URI.
	 *
	 * @see https://tools.ietf.org/html/rfc3986#section-2
	 * @see https://tools.ietf.org/html/rfc3986#section-3.5
	 * @return string The URI fragment.
	 */
	public function getFragment()
	{
		return $this->parts['fragment'] ?? '';
	}

	/**
	 * Return an instance with the specified scheme.
	 *
	 * @param string $scheme The scheme to use with the new instance.
	 * @return static A new instance with the specified scheme.
	 * @throws \InvalidArgumentException for invalid schemes.
	 * @throws \InvalidArgumentException for unsupported schemes.
	 */
	public function withScheme($scheme)
	{
		$new = new Uri($this->toString());
		$new->parts['scheme'] = $scheme;
		return $new;
	}

	/**
	 * Return an instance with the specified user information.
	 *
	 * @param string $user The user name to use for authority.
	 * @param null|string $password The password associated with $user.
	 * @return static A new instance with the specified user information.
	 */
	public function withUserInfo($user, $password = null)
	{
		$new = new Uri($this->toString());
		$new->parts['user'] = $user;
		$new->parts['pass'] = (empty($password) ? null : $password);
		return $new;
	}

	/**
	 * Return an instance with the specified host.
	 *
	 * @param string $host The hostname to use with the new instance.
	 * @return static A new instance with the specified host.
	 * @throws \InvalidArgumentException for invalid hostnames.
	 */
	public function withHost($host)
	{
		if (empty($host)) {
			throw new InvalidArgumentException('Host cannot be empty');
		}
		//
		$new = new Uri($this->toString());
		$new->parts['host'] = $host;
		return $new;
	}

	/**
	 * Return an instance with the specified port.
	 *
	 * @param null|int $port The port to use with the new instance; a null value
	 *	 removes the port information.
	 * @return static A new instance with the specified port.
	 * @throws \InvalidArgumentException for invalid ports.
	 */
	public function withPort($port)
	{
		if (!is_int($port) && !is_null($port)) {
			throw new InvalidArgumentException('Port must be either integer or null');
		}
		//
		if (($port < 0) || ($port > 65535)) {
			throw new InvalidArgumentException('Port must be between 0 and 65535, inclusive');
		}
		//
		$new = new Uri($this->toString());
		$new->parts['port'] = $port;
		return $new;
	}

	/**
	 * Return an instance with the specified path.
	 *
	 * @param string $path The path to use with the new instance.
	 * @return static A new instance with the specified path.
	 * @throws \InvalidArgumentException for invalid paths.
	 */
	public function withPath($path)
	{
		$new = new Uri($this->toString());
		$new->parts['path'] = $path;
		return $new;
	}

	/**
	 * Return an instance with the specified query string.
	 *
	 * @param string $query The query string to use with the new instance.
	 * @return static A new instance with the specified query string.
	 * @throws \InvalidArgumentException for invalid query strings.
	 */
	public function withQuery($query)
	{
		$new = new Uri('' . $this . '');
		$new->parts['query'] = $query;
		return $new;
	}

	/**
	 * Return an instance with the specified URI fragment.
	 *
	 * @param string $fragment The fragment to use with the new instance.
	 * @return static A new instance with the specified fragment.
	 */
	public function withFragment($fragment)
	{
		$new = new Uri('' . $this . '');
		$new->parts['fragment'] = $fragment;
		return $new;
	}

	/**
	 * Return the string representation as a URI reference.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->toString();
	}

	/**
	 * Return the string representation as a URI reference.
	 *
	 * @return string
	 */
	public function toString()
	{
		$result = '';
		// If a scheme is present, it MUST be suffixed by ":"
		if (!empty($this->parts['scheme'])) {
			$result .= $this->parts['scheme'] . ':';
		}
		// If an authority is present, it MUST be prefixed by "//"
		if (!empty($this->parts['authority'])) {
			if (!empty($this->parts['userInfo'])) {
				$result .= $this->parts['userInfo'];
			}
			//
			$result .= '//' . $this->parts['authority'];
		}
		//
		if (!empty($this->parts['path'])) {
			$path = $this->parts['path'];
			$authority = $this->parts['authority'];
			//
			if ('/' != substr($path, 0, 1)) {
				// If the path is rootless and an authority is present,
				// the path MUST be prefixed by "/".
				if (!empty($authority)) {
					$path = '/' . $path;
				}
			} else {
				// If the path is starting with more than one "/"
				// and no authority is present,
				// the starting slashes MUST be reduced to one.
				if (empty($authority)) {
					while (substr($path, 0, 2) == '//') {
						$path = substr($path, 1);
					}
				}
			}
			//
			$result .= $path;
		} else {
			$result .= '';
		}
		// If a query is present, it MUST be prefixed by "?"
		if (!empty($this->parts['query'])) {
			$result .= '?' . $this->parts['query'];
		}
		// If a fragment is present, it MUST be prefixed by "#"
		if (!empty($this->parts['fragment'])) {
			$result .= '#' . $this->parts['fragment'];
		}
		//
		return $result;
	}

}



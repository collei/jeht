<?php
namespace Jeht\Http;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface;
use Stringable as NativeStringable;
use Jeht\Support\Interfaces\Stringable;

/**
 * Representation of an outgoing, server-side response.
 *
 * IMPORTANT NOTE: the LICENSE note below does apply for some methods
 * and/or part of the implementation inside such methods of this class.
 * Most of these methods are:
 *
 *	isInvalid()
 *	isInformational()
 *	isSuccessful()
 *	isRedirection()
 *	isClientError()
 *	isServerError()
 *	isOk()
 *	isForbidden()
 *	isNotFound()
 *	isRedirect()
 *	isEmpty()
 *	setProtocolVersion()
 *	send()
 *	closeOutputBuffers()
 *
 * -------------------------------------------------------------------
 * Copyright (c) 2004-2021 Fabien Potencier
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 * -------------------------------------------------------------------
 */
class Response implements ResponseInterface
{
	/**
	 * Status codes and their reason phrases.
	 *
	 * @see http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
	 *
	 * @var array
	 */
	protected const HTTP_STATUS_CODES = [
		100 => 'Continue',
		101 => 'Switching Protocols',
		102 => 'Processing',
		103 => 'Early Hints',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		207 => 'Multi-Status',
		208 => 'Already Reported',
		226 => 'IM Used',
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		306 => '(Unused)',
		307 => 'Temporary Redirect',
		308 => 'Permanent Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Content Too Large',
		414 => 'URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Range Not Satisfiable',
		417 => 'Expectation Failed',
		418 => 'I\'m a teapot',
		419 => 'Page Expired',
		420 => 'Method Failure',
		421 => 'Misdirected Request',
		422 => 'Unprocessable Content',
		423 => 'Locked',
		424 => 'Failed Dependency',
		425 => 'Too Early',
		426 => 'Upgrade Required',
		427 => 'Unassigned',
		428 => 'Precondition Required',
		429 => 'Too Many Requests',
		430 => 'Unassigned',
		431 => 'Request Header Fields Too Large',
		451 => 'Unavailable For Legal Reasons',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		506 => 'Variant Also Negotiates',
		507 => 'Insufficient Storage',
		508 => 'Loop Detected',
		509 => 'Unassigned',
		510 => 'Not Extended (OBSOLETED)',
		511 => 'Network Authentication Required',
	];

	/**
	 * @var string
	 */
	protected $httpVersion = '';

	/**
	 * @var int
	 */
	protected $statusCode = '';

	/**
	 * @var string
	 */
	protected $reason = '';

	/**
	 * @var string[]
	 */
	protected $headers = [];

	/**
	 * @var \Psr\Http\Message\StreamInterface
	 */
	protected $body = '';

	/**
	 * @var \Jeht\Http\HttpCookie[]
	 */
	protected $cookies = [];

	/**
	 * Creates an instance with the specified body, status code and, optionally, headers.
	 *
	 * @see http://tools.ietf.org/html/rfc7231#section-6
	 * @see http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
	 * @param string|\Jeht\Support\Streams\Stream|\Psr\Http\Message\StreamInterface $body
	 * @param int $code
	 * @param array|null $headers
	 * @throws \InvalidArgumentException For invalid status code arguments
	 */
	public function __construct($body, int $statusCode = 200, array $headers = null)
	{
		if (!static::validateStatusCode($statusCode)) {
			throw new InvalidArgumentException("Invalid status code: [$statusCode].");
		}
		//
		if (
			$body instanceof StreamInterface || $body instanceof StringStream
		) {
			$this->body = clone $body;
		} elseif (
			is_string($body) || $body instanceof Stringable || $body instanceof NativeStringable
		) {
			$this->body = '' . $body . '';
		} else {
			$message = 'Body must be a string or instanceof \Stringable'
				. ' or instanceof [\Jeht\Support\Interfaces\Stringable]'
				. ' or instanceof [\Jeht\Support\Streams\Stream]'
				. ' or instanceof [\Psr\Http\Message\StreamInterface].';
			//
			throw new InvalidArgumentException($message);
		}
		//
		$this->body = $body;
		$this->statusCode = $statusCode;
		$this->reason = self::HTTP_STATUS_CODES[$statusCode] ?? '';
		//
		if ($headers) {
			foreach ($headers as $name => $value) {
				$this->addHeader($name, $value);
			}
		}
	}

	/**
	 * Validates a given $statusCode
	 *
	 * @param int $statusCode
	 * @return bool
	 */
	public static function validateStatusCode(int $statusCode)
	{
		return ($statusCode >= 100 && $statusCode < 600);
	}

	/**
	 * Is response invalid?
	 *
	 * @see https://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
	 *
	 * @return bool
	 */
	public function isInvalid()
	{
		return !self::validateStatusCode($this->statusCode);
	}

	/**
	 * Is response informative?
	 *
	 * @return bool
	 */
	public function isInformational()
	{
		return $this->statusCode >= 100 && $this->statusCode < 200;
	}

	/**
	 * Is response successful?
	 *
	 * @return bool
	 */
	public function isSuccessful()
	{
		return $this->statusCode >= 200 && $this->statusCode < 300;
	}

	/**
	 * Is the response a redirect?
	 *
	 * @return bool
	 */
	public function isRedirection()
	{
		return $this->statusCode >= 300 && $this->statusCode < 400;
	}

	/**
	 * Is there a client error?
	 *
	 * @return bool
	 */
	public function isClientError()
	{
		return $this->statusCode >= 400 && $this->statusCode < 500;
	}

	/**
	 * Was there a server side error?
	 *
	 * @return bool
	 */
	public function isServerError()
	{
		return $this->statusCode >= 500 && $this->statusCode < 600;
	}

	/**
	 * Is the response OK?
	 *
	 * @return bool
	 */
	public function isOk()
	{
		return 200 === $this->statusCode;
	}

	/**
	 * Is the response forbidden?
	 *
	 * @return bool
	 */
	public function isForbidden()
	{
		return 403 === $this->statusCode;
	}

	/**
	 * Is the response a not found error?
	 *
	 * @return bool
	 */
	public function isNotFound()
	{
		return 404 === $this->statusCode;
	}

	/**
	 * Is the response a redirect of some form?
	 *
	 * @return bool
	 */
	public function isRedirect(string $location = null): bool
	{
		return in_array($this->statusCode, [201, 301, 302, 303, 307, 308])
			&& (null === $location ?: $location == $this->getHeader('Location'));
	}

	/**
	 * Is the response empty?
	 *
	 * @final
	 */
	public function isEmpty(): bool
	{
		return in_array($this->statusCode, [204, 304]);
	}

	/**
	 * Retrieves the HTTP protocol version as a string.
	 *
	 * @return string HTTP protocol version.
	 */
	public function getProtocolVersion()
	{
		return $this->httpVersion;
	}

	/**
	 * Sets the HTTP protocol version as a string.
	 *
	 * @param string $version  HTTP protocol version.
	 * @return $this
	 */
	public function setProtocolVersion(string $version)
	{
		$this->httpVersion = $version;
		//
		return $this;
	}

	/**
	 * Return an instance with the specified HTTP protocol version.
	 *
	 * @param string $version HTTP protocol version
	 * @return static
	 */
	public function withProtocolVersion($version)
	{
		$cloned = clone $this;
		$cloned->httpVersion = $version;
		return $cloned;
	}

	/**
	 * Retrieves all message header values.
	 *
	 * @return string[][] Returns an associative array of the message's headers.
	 *	 Each key MUST be a header name, and each value MUST be an array of
	 *	 strings for that header.
	 */
	public function getHeaders()
	{
		return $this->headers;
	}

	/**
	 * Checks if a header exists by the given case-insensitive name.
	 *
	 * @param string $name Case-insensitive header field name.
	 * @return bool Returns true if any header names match the given header
	 *	 name using a case-insensitive string comparison. Returns false if
	 *	 no matching header name is found in the message.
	 */
	public function hasHeader($name)
	{
		if (empty($this->headers)) {
			return false;
		}
		//
		foreach ($this->headers as $n => $v) {
			if (0 == strcasecmp($n, $name)) {
				return true;
			}
		}
		//
		return false;
	}

	/**
	 * Retrieves a message header value by the given case-insensitive name.
	 *
	 * @param string $name Case-insensitive header field name.
	 * @return string[] An array of string values as provided for the given
	 *	header. If the header does not appear in the message, this method MUST
	 *	return an empty array.
	 */
	public function getHeader($name)
	{
		if (empty($this->headers)) {
			return [];
		}
		//
		foreach ($this->headers as $n => $v) {
			if (0 == strcasecmp($n, $name)) {
				return is_array($v) ? $v : [$v];
			}
		}
		//
		return [];
	}

	/**
	 * Retrieves a comma-separated string of the values for a single header.
	 *
	 * @param string $name Case-insensitive header field name.
	 * @return string A string of values as provided for the given header
	 *	concatenated together using a comma. If the header does not appear in
	 *	the message, this method MUST return an empty string.
	 */
	public function getHeaderLine($name)
	{
		if (empty($this->headers)) {
			return '';
		}
		//
		foreach ($this->headers as $n => $v) {
			if (0 == strcasecmp($n, $name)) {
				return is_array($v) ? implode(',', $v) : $v;
			}
		}
		//
		return '';
	}

	/**
	 * Return an instance with the provided value replacing the specified header.
	 *
	 * @param string $name Case-insensitive header field name.
	 * @param string|string[] $value Header value(s).
	 * @return static
	 * @throws \InvalidArgumentException for invalid header names or values.
	 */
	public function withHeader($name, $value)
	{
		$cloned = clone $this;
		$cloned->setHeader($name, $value);
		return $cloned;
	}

	/**
	 * Return an instance with the specified header appended with the given value.
	 *
	 * @param string $name Case-insensitive header field name to add.
	 * @param string|string[] $value Header value(s).
	 * @return static
	 * @throws \InvalidArgumentException for invalid header names.
	 * @throws \InvalidArgumentException for invalid header values.
	 */
	public function withAddedHeader($name, $value)
	{
		if (!is_string($name)) {
			throw new InvalidArgumentException('Name must be a string');
		}
		//
		if (!is_string($value) && !is_array($value)) {
			throw new InvalidArgumentException('Value must be a string or array');
		}
		//
		$cloned = clone $this;
		$cloned->addHeader($name, $value);
		return $cloned;
	}

	/**
	 * Return an instance without the specified header.
	 *
	 * @param string $name Case-insensitive header field name to remove.
	 * @return static
	 * @throws \InvalidArgumentException for invalid header names.
	 */
	public function withoutHeader($name)
	{
		if (!is_string($name)) {
			throw new InvalidArgumentException('Name must be a string');
		}
		//
		$cloned = clone $this;
		$cloned->unsetHeader($name);
		return $cloned;
	}

	/**
	 * Sets the header by replacing it.
	 *
	 * @param string $name Case-insensitive header field name.
	 * @param string|string[] $value Header value(s).
	 * @return $this
	 * @throws \InvalidArgumentException for invalid header names.
	 * @throws \InvalidArgumentException for invalid header values.
	 */
	public function setHeader($name, $value)
	{
		if (!is_string($name)) {
			throw new InvalidArgumentException('Name must be a string');
		}
		//
		if (!is_string($value) && !is_array($value)) {
			throw new InvalidArgumentException('Value must be a string or array');
		}
		//
		if (empty($this->headers)) {
			$this->headers = [
				$name => is_array($value) ? $value : [$value]
			];
		} else {
			$found = false;
			foreach ($this->headers as $n => $v) {
				if (0 == strcasecmp($n, $name)) {
					$this->headers[$n] = is_array($value) ? $value : [$value];
					$found = true;
					break;
				}
			}
			//
			if (!$found) {
				$this->headers[$name] = is_array($value) ? $value : [$value];
			}
		}
		//
		return $this;
	}

	/**
	 * Add the specified header to the current instance.
	 * Used by the constructor and also by withAddedHeader() upon the cloned.
	 *
	 * @param string $name Case-insensitive header field name to add.
	 * @param string|string[] $value Header value(s).
	 * @return $this
	 * @throws \InvalidArgumentException for invalid header names.
	 * @throws \InvalidArgumentException for invalid header values.
	 */
	public function addHeader($name, $value)
	{
		if (!is_string($name)) {
			throw new InvalidArgumentException('Name must be a string');
		}
		//
		if (!is_string($value) && !is_array($value)) {
			throw new InvalidArgumentException('Value must be a string or array');
		}
		//
		if (empty($this->headers)) {
			$this->headers = [
				$name => is_array($value) ? $value : [$value]
			];
		} else {
			$found = false;
			foreach ($this->headers as $n => $v) {
				if (0 == strcasecmp($n, $name)) {
					if (!is_array($v)) {
						$this->headers[$n] = [$v];
					}
					//
					if (is_array($value)) {
						foreach ($value as $valueItem) {
							$this->headers[$n][] = $valueItem;
						}
					} else {
						$this->headers[$n][] = $value;
					}
					//
					$found = true;
					break;
				}
			}
			//
			if (!$found) {
				$this->headers[$name] = is_array($value) ? $value : [$value];
			}
		}
		//
		return $this;
	}

	/**
	 * Removes the specified header.
	 *
	 * @param string $name Case-insensitive header field name to remove.
	 * @return $this
	 * @throws \InvalidArgumentException for invalid header names.
	 */
	public function unsetHeader($name)
	{
		if (!is_string($name)) {
			throw new InvalidArgumentException('Name must be a string');
		}
		//
		if (!empty($this->headers)) {
			foreach ($this->headers as $n => $v) {
				if (0 == strcasecmp($n, $name)) {
					unset($this->headers[$n]);
					break;
				}
			}
		}
		//
		return $this;
	}

	/**
	 * Gets the body of the message.
	 *
	 * @return StreamInterface Returns the body as a stream.
	 */
	public function getBody()
	{
		return $this->body;
	}

	/**
	 * Return an instance with the specified message body.
	 *
	 * @param StreamInterface $body Body.
	 * @return static
	 * @throws \InvalidArgumentException When the body is not valid.
	 */
	public function withBody(StreamInterface $body)
	{
		$cloned = clone $this;
		$cloned->setBody($body);
		return $cloned;
	}

	/**
	 * Return an instance with the specified message body.
	 *
	 * @return static
	 */
	public function withoutBody()
	{
		$cloned = clone $this;
		$cloned->setContent(null);
		return $cloned;
	}

	/**
	 * Sets the body of the message.
	 *
	 * @param \Psr\Http\Message\StreamInterface
	 * @return void
	 */
	protected function setBody(StreamInterface $body)
	{
		$this->body = null;
		$this->body = $body;
	}

	/**
	 * Sets the body of the message by using a string.
	 *
	 * @param string|null $content
	 * @return $this
	 */
	public function setContent(string $content = null)
	{
		$this->setBody(
			new StringStream($content ?? '')
		);
		//
		return $this;
	}

	/**
	 * Sets the body of the message by using a string.
	 *
	 * @return $this
	 */
	public function unsetContent()
	{
		return $this->setContent();
	}

	/**
	 * Gets the response status code.
	 *
	 * The status code is a 3-digit integer result code of the server's attempt
	 * to understand and satisfy the request.
	 *
	 * @return int Status code.
	 */
	public function getStatusCode()
	{
		return $this->statusCode;
	}

	/**
	 * Return an instance with the specified status code and, optionally, reason phrase.
	 *
	 * @see http://tools.ietf.org/html/rfc7231#section-6
	 * @see http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
	 * @param int $code The 3-digit integer result code to set.
	 * @param string $reasonPhrase The reason phrase to use with the
	 *	 provided status code; if none is provided, implementations MAY
	 *	 use the defaults as suggested in the HTTP specification.
	 * @return static
	 * @throws \InvalidArgumentException For invalid status code arguments.
	 */
	public function withStatus($code, $reasonPhrase = '')
	{
		if (!is_int($code)) {
			throw new InvalidArgumentException('$code must be an integer.');
		} elseif (!static::validateStatusCode($code)) {
			throw new InvalidArgumentException("Invalid status code: [$code].");
		}
		//
		$cloned = clone $this;
		//
		$cloned->statusCode = $code;
		$cloned->reason = $reasonPhrase ?? self::HTTP_STATUS_CODES[$statusCode] ?? '';
		//
		return $cloned;
	}

	/**
	 * Gets the response reason phrase associated with the status code.
	 *
	 * @see http://tools.ietf.org/html/rfc7231#section-6
	 * @see http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
	 * @return string Reason phrase; must return an empty string if none present.
	 */
	public function getReasonPhrase()
	{
		return $this->reason ?? '';
	}

	/**
	 * Returns the response cookies as array of \Jeht\Http\HttpCookie
	 *
	 * @return array
	 */
	public function getCookies()
	{
		return $cookies = $this->cookies;
	}

	/**
	 * Returns the response cookies as array of \Jeht\Http\HttpCookie
	 *
	 * @return array
	 */
	public function getCharset(string $default = null)
	{
		return $default;
	}

	/**
	 * Send HTTP headers and content.
	 *
	 * @return $this
	 */
	public function send()
	{
		$this->sendHeaders();
		$this->sendContent();
		//
		if (function_exists('fastcgi_finish_request')) {
			fastcgi_finish_request();
		} elseif (!in_array(PHP_SAPI, ['cli', 'phpdbg'], true)) {
			static::closeOutputBuffers(0, true);
		}
		//
		return $this;
	}

	/**
	 * Send the HTTP headers.
	 *
	 * @return $this
	 */
	protected function sendHeaders()
	{
		if (headers_sent()) {
			return $this;
		}
		//
		foreach ($this->getHeaders() as $name => $values) {
			$replace = 0 === strcasecmp($name, 'Content-Type');
			//
			foreach ($values as $value) {
				header($name.': '.$value, $replace, $this->statusCode);
			}
		}
		//
		foreach ($this->cookies as $cookie) {
			header($cookie->asHeaderString(), false, $this->statusCode);
		}
		//
		$status = 'HTTP/'.$this->httpVersion.' '.$this->statusCode;
		//
		if (!empty($this->reason)) {
			$status .= ' '.$this->reason;
		} else {
			$status .= ' '.(self::HTTP_STATUS_CODES[$this->statusCode] ?? 'Undefined');
		}
		//
		header($status, true, $this->statusCode);
		//
		return $this;
	}

	/**
	 * Send HTTP content.
	 *
	 * @return $this
	 */
	protected function sendContent()
	{
		echo $this->getBody()->rewind()->getContents();
		//
		return $this;
	}

	/**
	 * Cleans or flushes output buffers up to target level.
	 *
	 * Resulting level can be greater than target level if a non-removable buffer has been encountered.
	 *
	 *
	 *
	 *
	 *
	 *
	 * @return void
	 */
	public static function closeOutputBuffers(int $targetLevel, bool $flush): void
	{
		$status = ob_get_status(true);
		$level = count($status);
		$flags = PHP_OUTPUT_HANDLER_REMOVABLE | ($flush ? PHP_OUTPUT_HANDLER_FLUSHABLE : PHP_OUTPUT_HANDLER_CLEANABLE);
		//
		while (
			$level-- > $targetLevel
			&& ($st = $status[$level])
			&& (!isset($st['del']) ? !isset($st['flags']) || ($st['flags'] & $flags) === $flags : $st['del'])
		) {
			if ($flush) {
				ob_end_flush();
			} else {
				ob_end_clean();
			}
		}
	}


}


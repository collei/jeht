<?php
namespace Jeht\Http;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Representation of an outgoing, server-side response.
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
	 * @var string
	 */
	protected $body = '';

	/**
	 * Creates an instance with the specified body, status code and, optionally, headers.
	 *
	 * @see http://tools.ietf.org/html/rfc7231#section-6
	 * @see http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
	 * @param string $body
	 * @param int $code
	 * @param array|null $headers
	 * @throws \InvalidArgumentException For invalid status code arguments
	 */
	public function __construct(string $body, int $statusCode = 200, array $headers = null)
	{
		if (!static::validateStatusCode($statusCode)) {
			throw new InvalidArgumentException("Invalid status code: [$statusCode].");
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
		return array_key_exists($statusCode, self::HTTP_STATUS_CODES)
				|| ($statusCode >= 100 && $statusCode <= 599);
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
		if (!is_string($name)) {
			throw new InvalidArgumentException('Name must be a string');
		}
		//
		if (!is_string($value) && !is_array($value)) {
			throw new InvalidArgumentException('Value must be a string or array');
		}
		//
		$cloned = clone $this;
		//
		if (empty($cloned->headers)) {
			$cloned->headers = [
				$name => is_array($value) ? $value : [$value]
			];
		} else {
			$found = false;
			foreach ($cloned->headers as $n => $v) {
				if (0 == strcasecmp($n, $name)) {
					$cloned->headers[$n] = is_array($value) ? $value : [$value];
					$found = true;
					break;
				}
			}
			//
			if (!$found) {
				$cloned->headers[$name] = is_array($value) ? $value : [$value];
			}
		}
		//
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
	 * Add the specified header to the current instance.
	 * Used by the constructor and also by withAddedHeader() upon the cloned.
	 *
	 * @param string $name Case-insensitive header field name to add.
	 * @param string|string[] $value Header value(s).
	 * @return void
	 * @throws \InvalidArgumentException for invalid header names.
	 * @throws \InvalidArgumentException for invalid header values.
	 */
	protected function addHeader($name, $value)
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
	}

	/**
	 * Return an instance without the specified header.
	 *
	 * @param string $name Case-insensitive header field name to remove.
	 * @return static
	 */
	public function withoutHeader($name)
	{
		if (!is_string($name)) {
			throw new InvalidArgumentException('Name must be a string');
		}
		//
		$new = new static;
		//
		if (!empty($new->headers)) {
			foreach ($new->headers as $n => $v) {
				if (0 == strcasecmp($n, $name)) {
					unset($new->headers[$n]);
					break;
				}
			}
		}
		//
		return $new;
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
		$cloned->body = $body;
		return $cloned;
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

}


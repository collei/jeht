<?php
namespace Jeht\Http;

use Jeht\Container\Container;
use JsonSerializable;
use Jeht\Interfaces\Support\Responsable;
use Jeht\Interfaces\Support\Jsonable;
use Jeht\Interfaces\Support\Arrayable;
use Jeht\Interfaces\Support\Stringable;
use Jeht\Support\ArrayObject;

class ResponsePreparator
{
	/**
	 * @var $responseFactory
	 */
	private $responseFactory;

	/**
	 * Initializes a preparator instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->responseFactory = new ResponseFactory;
	}

	/**
	 * Prepares the $response in the appropriate format.
	 *
	 * @param \Jeht\Http\Request $request
	 * @param mixed $response
	 * @return \Jeht\Http\Response
	 */
	public function prepare(Request $request, $response)
	{
		if ($response instanceof Responsable) {
			$response = $response->toResponse($request);
		}
		//
		if ($response instanceof Stringable) {
			$response = $this->create($response->__toString(), 200, ['Content-Type' => 'text/html']);
		} elseif (is_string($response)) {
			$response = $this->create($response, 200, ['Content-Type' => 'text/html']);
		} elseif (
			$response instanceof Arrayable ||
			$response instanceof Jsonable ||
			$response instanceof JsonSerializable ||
			$response instanceof ArrayObject ||
			is_array($response)
		) {
			$response = new JsonResponse($response);
		}
		//
		return $this->finalPreparations($request, $response);
	}

	/**
	 * Creates an instance with the specified body, status code and, optionally, headers.
	 *
	 * @param string $body
	 * @param int $code
	 * @param array|null $headers
	 * @throws \InvalidArgumentException For invalid status code arguments
	 */
	protected function create(string $content, int $code, array $headers = null)
	{
		return $this->responseFactory->create($content, $code, $headers);
	}

	/**
	 * Makes final preparation on the Response before it is sent to the client.
	 *
	 * This method tweaks the Response to ensure that it is
	 * compliant with RFC 2616. Most of the changes are based on
	 * the Request that is "associated" with this Response.
	 *
	 * Adapted from Symfony's Symfony\Component\HttpFoundation\Response::prepare
	 * @link https://github.com/symfony/symfony/blob/6.3/src/Symfony/Component/HttpFoundation/Response.php
	 * @link https://github.com/symfony/symfony/blob/6.3/src/Symfony/Component/HttpFoundation/Response.php#L261
	 *
	 * @return $this
	 */
	protected function finalPreparations(Request $request, Response $response)
	{
		if ($response->isInformational() || $response->isEmpty()) {
			$response->setContent(null);
			$headers->remove('Content-Type');
			$headers->remove('Content-Length');
			// prevent PHP from sending the Content-Type header based on default_mimetype
			ini_set('default_mimetype', '');
		} else {
			// Content-type based on the Request
			if (!$headers->has('Content-Type')) {
				$format = $request->getRequestFormat(null);
				if (null !== $format && $mimeType = $request->getMimeType($format)) {
					$headers->set('Content-Type', $mimeType);
				}
			}

			// Fix Content-Type
			$charset = $this->charset ?: 'UTF-8';
			if (!$headers->has('Content-Type')) {
				$headers->set('Content-Type', 'text/html; charset='.$charset);
			} elseif (0 === stripos($headers->get('Content-Type'), 'text/') && false === stripos($headers->get('Content-Type'), 'charset')) {
				// add the charset
				$headers->set('Content-Type', $headers->get('Content-Type').'; charset='.$charset);
			}

			// Fix Content-Length
			if ($headers->has('Transfer-Encoding')) {
				$headers->remove('Content-Length');
			}

			if ($request->isMethod('HEAD')) {
				// cf. RFC2616 14.13
				$length = $headers->get('Content-Length');
				$this->setContent(null);
				if ($length) {
					$headers->set('Content-Length', $length);
				}
			}
		}

		// Fix protocol
		if ('HTTP/1.0' != $request->server->get('SERVER_PROTOCOL')) {
			$this->setProtocolVersion('1.1');
		}

		// Check if we need to send extra expire info headers
		if ('1.0' == $this->getProtocolVersion() && false !== strpos($headers->get('Cache-Control'), 'no-cache')) {
			$headers->set('pragma', 'no-cache');
			$headers->set('expires', -1);
		}

		$this->ensureIEOverSSLCompatibility($request);

		if ($request->isSecure()) {
			foreach ($headers->getCookies() as $cookie) {
				$cookie->setSecureDefault(true);
			}
		}

		return $this;
	}

}



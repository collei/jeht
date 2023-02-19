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
		/*
		 * Content fixes
		 */
		if ($response->isInformational() || $response->isEmpty()) {
			$response->unsetContent()
					->unsetHeader('Content-Type')
					->unsetHeader('Content-Length');
			// prevent PHP from sending the Content-Type header based on default_mimetype
			ini_set('default_mimetype', '');
		} else {
			// Content-type based on the Request
			if (!$response->hasHeader('Content-Type')) {
				$format = $request->getRequestFormat(null);
				//
				if (null !== $format && $mimeType = $request->getMimeType($format)) {
					$response->setHeader('Content-Type', $mimeType);
				}
			}
			//
			// Fix Content-Type
			$charset = $response->getCharset('UTF-8');
			//
			if (!$response->hasHeader('Content-Type')) {
				$response->setHeader('Content-Type', 'text/html; charset='.$charset);
			} elseif (
				0 === stripos($response->getHeaderLine('Content-Type'), 'text/') &&
				false === stripos($response->getHeaderLine('Content-Type'), 'charset')
			) {
				// add the charset
				$response->setHeader(
					'Content-Type', $response->getHeaderLine('Content-Type').'; charset='.$charset
				);
			}
			//
			// Fix Content-Length
			if ($response->hasHeader('Transfer-Encoding')) {
				$response->unsetHeader('Content-Length');
			}
			//
			if ($request->isMethod('HEAD')) {
				// cf. RFC2616 14.13
				$length = $response->getHeader('Content-Length');
				$response->unsetContent();
				//
				if ($length) {
					$response->setHeader('Content-Length', $length);
				}
			}
		}

		/*
		 * Protocol fixes
		 */
		if ('HTTP/1.0' != $request->getServerParam('SERVER_PROTOCOL')) {
			$response->setProtocolVersion('1.1');
		}

		/*
		 * Check if we need to send extra expire info headers
		 */
		if (
			'1.0' == $response->getProtocolVersion() &&
			false !== strpos($response->getHeader('Cache-Control'), 'no-cache')
		) {
			$response->setHeader('pragma', 'no-cache')
					->setHeader('expires', -1);
		}

		/*
		 * IE over SSL compatibility
		 */
		//$response->ensureIEOverSSLCompatibility($request);

		/*
		 * Secure cookies
		 */
		if ($request->isSecure()) {
			foreach ($response->getCookies() as $cookie) {
				$cookie->setSecureDefault(true);
			}
		}

		return $response;
	}

}



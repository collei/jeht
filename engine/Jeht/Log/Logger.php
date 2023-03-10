<?php
namespace Jeht\Log;

use Closure;
use Jeht\Events\Interfaces\Dispatcher;
use Jeht\Support\Interfaces\Arrayable;
use Jeht\Support\Interfaces\Jsonable;
use Jeht\Log\Events\MessageLogged;
use Jeht\Events\Interfaces\DispatcherInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Adapted from Laravel's Illuminate\Log\Logger
 * @link https://laravel.com/api/8.x/Illuminate/Log/Logger.html
 * @link https://github.com/laravel/framework/blob/8.x/src/Illuminate/Log/Logger.php
 *
 */
class Logger implements LoggerInterface
{
	/**
	 * The underlying logger implementation.
	 *
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $logger;

	/**
	 * The event dispatcher instance.
	 *
	 * @var \Jeht\Events\Interfaces\DispatcherInterface|null
	 */
	protected $dispatcher;

	/**
	 * Any context to be added to logs.
	 *
	 * @var array
	 */
	protected $context = [];

	/**
	 * Create a new log writer instance.
	 *
	 * @param  \Psr\Log\LoggerInterface  $logger
	 * @param  \Jeht\Events\Interfaces\DispatcherInterface|null  $dispatcher
	 * @return void
	 */
	public function __construct(LoggerInterface $logger, DispatcherInterface $dispatcher = null)
	{
		$this->logger = $logger;
		$this->dispatcher = $dispatcher;
	}

	/**
	 * Log an emergency message to the logs.
	 *
	 * @param  string  $message
	 * @param  array  $context
	 * @return void
	 */
	public function emergency(string|\Stringable $message, array $context = []): void
	{
		$this->writeLog(__FUNCTION__, $message, $context);
	}

	/**
	 * Log an alert message to the logs.
	 *
	 * @param  string  $message
	 * @param  array  $context
	 * @return void
	 */
	public function alert(string|\Stringable $message, array $context = []): void
	{
		$this->writeLog(__FUNCTION__, $message, $context);
	}

	/**
	 * Log a critical message to the logs.
	 *
	 * @param  string  $message
	 * @param  array  $context
	 * @return void
	 */
	public function critical(string|\Stringable $message, array $context = []): void
	{
		$this->writeLog(__FUNCTION__, $message, $context);
	}

	/**
	 * Log an error message to the logs.
	 *
	 * @param  string  $message
	 * @param  array  $context
	 * @return void
	 */
	public function error(string|\Stringable $message, array $context = []): void
	{
		$this->writeLog(__FUNCTION__, $message, $context);
	}

	/**
	 * Log a warning message to the logs.
	 *
	 * @param  string  $message
	 * @param  array  $context
	 * @return void
	 */
	public function warning(string|\Stringable $message, array $context = []): void
	{
		$this->writeLog(__FUNCTION__, $message, $context);
	}

	/**
	 * Log a notice to the logs.
	 *
	 * @param  string  $message
	 * @param  array  $context
	 * @return void
	 */
	public function notice(string|\Stringable $message, array $context = []): void
	{
		$this->writeLog(__FUNCTION__, $message, $context);
	}

	/**
	 * Log an informational message to the logs.
	 *
	 * @param  string  $message
	 * @param  array  $context
	 * @return void
	 */
	public function info(string|\Stringable $message, array $context = []): void
	{
		$this->writeLog(__FUNCTION__, $message, $context);
	}

	/**
	 * Log a debug message to the logs.
	 *
	 * @param  string  $message
	 * @param  array  $context
	 * @return void
	 */
	public function debug(string|\Stringable $message, array $context = []): void
	{
		$this->writeLog(__FUNCTION__, $message, $context);
	}

	/**
	 * Log a message to the logs.
	 *
	 * @param  string  $level
	 * @param  string  $message
	 * @param  array  $context
	 * @return void
	 */
	public function log($level, string|\Stringable $message, array $context = []): void
	{
		$this->writeLog($level, $message, $context);
	}

	/**
	 * Dynamically pass log calls into the writer.
	 *
	 * @param  string  $level
	 * @param  string  $message
	 * @param  array  $context
	 * @return void
	 */
	public function write($level, string|\Stringable $message, array $context = []): void
	{
		$this->writeLog($level, $message, $context);
	}

	/**
	 * Write a message to the log.
	 *
	 * @param  string  $level
	 * @param  string  $message
	 * @param  array  $context
	 * @return void
	 */
	protected function writeLog($level, string|\Stringable $message, $context): void
	{
		$this->logger->{$level}(
			$message = $this->formatMessage($message),
			$context = array_merge($this->context, $context)
		);

		$this->fireLogEvent($level, $message, $context);
	}

	/**
	 * Add context to all future logs.
	 *
	 * @param  array  $context
	 * @return $this
	 */
	public function withContext(array $context = [])
	{
		$this->context = array_merge($this->context, $context);

		return $this;
	}

	/**
	 * Flush the existing context array.
	 *
	 * @return $this
	 */
	public function withoutContext()
	{
		$this->context = [];

		return $this;
	}

	/**
	 * Register a new callback handler for when a log event is triggered.
	 *
	 * @param  \Closure  $callback
	 * @return void
	 *
	 * @throws \RuntimeException
	 */
	public function listen(Closure $callback)
	{
		if (! isset($this->dispatcher)) {
			throw new RuntimeException('Events dispatcher has not been set.');
		}

		$this->dispatcher->listen(MessageLogged::class, $callback);
	}

	/**
	 * Fires a log event.
	 *
	 * @param  string  $level
	 * @param  string  $message
	 * @param  array  $context
	 * @return void
	 */
	protected function fireLogEvent($level, $message, array $context = [])
	{
		// If the event dispatcher is set, we will pass along the parameters to the
		// log listeners. These are useful for building profilers or other tools
		// that aggregate all of the log messages for a given "request" cycle.
		if (isset($this->dispatcher)) {
			$this->dispatcher->dispatch(new MessageLogged($level, $message, $context));
		}
	}

	/**
	 * Format the parameters for the logger.
	 *
	 * @param  mixed  $message
	 * @return mixed
	 */
	protected function formatMessage($message)
	{
		if (is_array($message)) {
			return var_export($message, true);
		} elseif ($message instanceof Jsonable) {
			return $message->toJson();
		} elseif ($message instanceof Arrayable) {
			return var_export($message->toArray(), true);
		}

		return $message;
	}

	/**
	 * Get the underlying logger implementation.
	 *
	 * @return \Psr\Log\LoggerInterface
	 */
	public function getLogger()
	{
		return $this->logger;
	}

	/**
	 * Get the event dispatcher instance.
	 *
	 * @return \Jeht\Events\Interfaces\DispatcherInterface
	 */
	public function getEventDispatcher()
	{
		return $this->dispatcher;
	}

	/**
	 * Set the event dispatcher instance.
	 *
	 * @param  \Jeht\Events\Interfaces\DispatcherInterface  $dispatcher
	 * @return void
	 */
	public function setEventDispatcher(DispatcherInterface $dispatcher)
	{
		$this->dispatcher = $dispatcher;
	}

	/**
	 * Dynamically proxy method calls to the underlying logger.
	 *
	 * @param  string  $method
	 * @param  array  $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		return $this->logger->{$method}(...$parameters);
	}
}


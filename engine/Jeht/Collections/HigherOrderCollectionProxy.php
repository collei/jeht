<?php
namespace Jeht\Collections;

/**
 * From Laravel's Illuminate\Support\HigherOrderCollectionProxy
 * @link https://laravel.com/api/8.x/Illuminate/Support/HigherOrderCollectionProxy.html
 * @link https://github.com/laravel/framework/blob/8.x/src/Illuminate/Collections/HigherOrderCollectionProxy.php
 *
 * @mixin \Jeht\Collections\Enumerable
 */
class HigherOrderCollectionProxy
{
	/**
	 * The collection being operated on.
	 *
	 * @var \Jeht\Collections\Enumerable
	 */
	protected $collection;

	/**
	 * The method being proxied.
	 *
	 * @var string
	 */
	protected $method;

	/**
	 * Create a new proxy instance.
	 *
	 * @param  \Jeht\Collections\Enumerable  $collection
	 * @param  string  $method
	 * @return void
	 */
	public function __construct(Enumerable $collection, $method)
	{
		$this->method = $method;
		$this->collection = $collection;
	}

	/**
	 * Proxy accessing an attribute onto the collection items.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->collection->{$this->method}(function ($value) use ($key) {
			return is_array($value) ? $value[$key] : $value->{$key};
		});
	}

	/**
	 * Proxy a method call onto the collection items.
	 *
	 * @param  string  $method
	 * @param  array  $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		return $this->collection->{$this->method}(function ($value) use ($method, $parameters) {
			return $value->{$method}(...$parameters);
		});
	}
}


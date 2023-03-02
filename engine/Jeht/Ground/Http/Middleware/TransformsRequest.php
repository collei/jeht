<?php
namespace Jeht\Ground\Http\Middleware;

use Closure;
use Jeht\Collections\Collection;

/**
 * Adapted from Laravel's Illuminate\Foundation\Http\Middleware\TransformsRequest
 * @link https://laravel.com/api/8.x/Illuminate/Foundation/Http/Middleware/TransformsRequest.html
 * @link https://github.com/laravel/framework/blob/8.x/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php
 *
 */
class TransformsRequest
{
	/**
	 * Handle an incoming request.
	 *
	 * @param  \Jeht\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		$request = $this->clean($request);

		return $next($request);
	}

	/**
	 * Clean the request's data.
	 *
	 * @param  \Jeht\Http\Request  $request
	 * @return $request
	 */
	protected function clean($request)
	{
		$request = $request->withQueryParams(
			$this->cleanArray($request->getQueryParams())
		);

		$data = $request->getParsedBody();


		$data = is_object($data)
			? $this->cleanObject($data)
			: $this->cleanArray($data);

		return $request->withParsedBody($data);
	}

	/**
	 * Clean the data in the given array.
	 *
	 * @param  array  $data
	 * @param  string  $keyPrefix
	 * @return array
	 */
	protected function cleanArray(array $data, $keyPrefix = '')
	{
		foreach ($data as $key => $value) {
			$data[$key] = $this->cleanValue($keyPrefix.$key, $value);
		}

		return Collection::for($data)->all();
	}

	/**
	 * Clean the data in the given object.
	 *
	 * @param  object  $data
	 * @return object
	 */
	protected function cleanObject(object $data)
	{
		foreach ($data as $key => $value) {
			$data->$key = $this->cleanValue($key, $value);
		}

		return $data;
	}

	/**
	 * Clean the given value.
	 *
	 * @param  string  $key
	 * @param  mixed  $value
	 * @return mixed
	 */
	protected function cleanValue($key, $value)
	{
		if (is_array($value)) {
			return $this->cleanArray($value, $key.'.');
		}

		return $this->transform($key, $value);
	}

	/**
	 * Transform the given value.
	 *
	 * @param  string  $key
	 * @param  mixed  $value
	 * @return mixed
	 */
	protected function transform($key, $value)
	{
		return $value;
	}
}


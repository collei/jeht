<?php
namespace Jeht\Ground\Http\Middleware;

use Closure;

/**
 * Adapted from Laravel's Illuminate\Foundation\Http\Middleware\TrimStrings
 * @link https://laravel.com/api/8.x/Illuminate/Foundation/Http/Middleware/TrimStrings.html
 * @link https://github.com/laravel/framework/blob/8.x/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php
 *
 */
class TrimStrings extends TransformsRequest
{
	/**
	 * All of the registered skip callbacks.
	 *
	 * @var array
	 */
	protected static $skipCallbacks = [];

	/**
	 * The attributes that should not be trimmed.
	 *
	 * @var array
	 */
	protected $except = [
		//
	];

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Jeht\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		foreach (static::$skipCallbacks as $callback) {
			if ($callback($request)) {
				return $next($request);
			}
		}

		return parent::handle($request, $next);
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
		if (in_array($key, $this->except, true)) {
			return $value;
		}

		return is_string($value) ? trim($value) : $value;
	}

	/**
	 * Register a callback that instructs the middleware to be skipped.
	 *
	 * @param  \Closure  $callback
	 * @return void
	 */
	public static function skipWhen(Closure $callback)
	{
		static::$skipCallbacks[] = $callback;
	}
}


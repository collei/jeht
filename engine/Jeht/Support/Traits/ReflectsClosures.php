<?php
namespace Jeht\Support\Traits;

use Closure;
use Jeht\Support\Reflector;
use ReflectionFunction;
use RuntimeException;

/**
 * from laravel's Illuminate\Support\Traits\ReflectsClosures
 * @link https://laravel.com/api/8.x/Illuminate/Support/Traits/ReflectsClosures.html
 * @link https://github.com/laravel/framework/blob/8.x/src/Illuminate/Support/Traits/ReflectsClosures.php
 *
 */
trait ReflectsClosures
{
	/**
	 * Get the class name of the first parameter of the given Closure.
	 *
	 * @param  \Closure  $closure
	 * @return string
	 *
	 * @throws \ReflectionException
	 * @throws \RuntimeException
	 */
	protected function firstClosureParameterType(Closure $closure)
	{
		$types = array_values($this->closureParameterTypes($closure));

		if (! $types) {
			throw new RuntimeException('The given Closure has no parameters.');
		}

		if ($types[0] === null) {
			throw new RuntimeException('The first parameter of the given Closure is missing a type hint.');
		}

		return $types[0];
	}

	/**
	 * Get the class names of the first parameter of the given Closure, including union types.
	 *
	 * @param  \Closure  $closure
	 * @return array
	 *
	 * @throws \ReflectionException
	 * @throws \RuntimeException
	 */
	protected function firstClosureParameterTypes(Closure $closure)
	{
		$reflection = new ReflectionFunction($closure);

		$types = collect($reflection->getParameters())->mapWithKeys(function ($parameter) {
			if ($parameter->isVariadic()) {
				return [$parameter->getName() => null];
			}

			return [$parameter->getName() => Reflector::getParameterClassNames($parameter)];
		})->filter()->values()->all();

		if (empty($types)) {
			throw new RuntimeException('The given Closure has no parameters.');
		}

		if (isset($types[0]) && empty($types[0])) {
			throw new RuntimeException('The first parameter of the given Closure is missing a type hint.');
		}

		return $types[0];
	}

	/**
	 * Get the class names / types of the parameters of the given Closure.
	 *
	 * @param  \Closure  $closure
	 * @return array
	 *
	 * @throws \ReflectionException
	 */
	protected function closureParameterTypes(Closure $closure)
	{
		$reflection = new ReflectionFunction($closure);

		return collect($reflection->getParameters())->mapWithKeys(function ($parameter) {
			if ($parameter->isVariadic()) {
				return [$parameter->getName() => null];
			}

			return [$parameter->getName() => Reflector::getParameterClassName($parameter)];
		})->all();
	}
}


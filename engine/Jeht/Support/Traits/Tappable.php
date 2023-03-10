<?php
namespace Jeht\Support\Traits;

trait Tappable
{
	/**
	 * Call the given Closure with this instance then return the instance.
	 *
	 * @param  callable|null  $callback
	 * @return $this|\Jeht\Support\HigherOrderTapProxy
	 */
	public function tapMe($callback = null)
	{
		return $this->tapValue($this, $callback);
	}

	/**
	 * Call the given Closure with the $value then return the $value.
	 *
	 * @param  mixed  $value
	 * @param  callable|null  $callback
	 * @return $this|\Jeht\Support\HigherOrderTapProxy
	 */
	protected function tapValue($value, $callback = null)
	{
		if (is_null($callback)) {
			return new HigherOrderTapProxy($value);
		}

		$callback($value);

		return $value;
	}
}


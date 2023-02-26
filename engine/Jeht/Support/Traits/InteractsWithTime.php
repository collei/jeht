<?php
namespace Jeht\Support\Traits;

use DateInterval;
use DateTime;
use DateTimeInterface;

/**
 * Adapted from Laravel's Illuminate\Support\InteractsWithTime
 * @link https://laravel.com/api/8.x/Illuminate/Support/InteractsWithTime.html
 * @link https://github.com/laravel/framework/blob/8.x/src/Illuminate/Support/InteractsWithTime.php
 *
 */
trait InteractsWithTime
{
	/**
	 * Get the number of seconds until the given DateTime.
	 *
	 * @param  \DateTimeInterface|\DateInterval|int  $delay
	 * @return int
	 */
	protected function secondsUntil($delay)
	{
		$delay = $this->parseDateInterval($delay);
		//
		return $delay instanceof DateTimeInterface
			? max(0, $delay->getTimestamp() - $this->currentTime())
			: (int) $delay;
	}

	/**
	 * Get the "available at" UNIX timestamp.
	 *
	 * @param  \DateTimeInterface|\DateInterval|int  $delay
	 * @return int
	 */
	protected function availableAt($delay = 0)
	{
		$delay = $this->parseDateInterval($delay);
		//
		return $delay instanceof DateTimeInterface
			? $delay->getTimestamp()
			: $this->addRealSecondsTo($this->now(), $delay)->getTimestamp();
	}

	/**
	 * If the given value is an interval, convert it to a DateTime instance.
	 *
	 * @param  \DateTimeInterface|\DateInterval|int  $delay
	 * @return \DateTimeInterface|int
	 */
	protected function parseDateInterval($delay)
	{
		if ($delay instanceof DateInterval) {
			$delay = $this->now()->add($delay);
		}
		//
		return $delay;
	}

	/**
	 * Get the current system time as a UNIX timestamp.
	 *
	 * @return int
	 */
	protected function currentTime()
	{
		return $this->now()->getTimestamp();
	}

	/**
	 * Get the current system time as a \DateTime instance.
	 *
	 * @return \DateTime
	 */
	protected function now()
	{
		return new DateTime("now");
	}

	/**
	 * Get one of the most far future dates that could be comfortably achieved
	 * as a \DateTime instance.
	 *
	 * @return \DateTime
	 */
	protected function forever()
	{
		return new DateTime("2999-12-31");
	}

	/**
	 * Get the current system time as a UNIX timestamp.
	 *
	 * @return \DateTime
	 */
	protected function addRealSecondsTo(DateTime $original, int $seconds)
	{
		$another = clone $original;
		//
		return $another->add(new DateInterval("PT{$seconds}S"));
	}

}


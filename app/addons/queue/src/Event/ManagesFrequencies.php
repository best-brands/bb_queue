<?php

namespace Tygh\Addons\Queue\Event;

use Carbon\Carbon;
use Closure;
use DateTimeZone;
use Tygh\Addons\Queue\Schedule;

/**
 * Manages frequencies trait.
 */
trait ManagesFrequencies
{
    /**
     * The Cron expression representing the event's frequency.
     *
     * @param string $expression
     *
     * @return $this
     */
    public function cron(string $expression): self
    {
        $this->expression = $expression;

        return $this;
    }

    /**
     * Schedule the event to run between start and end time.
     *
     * @param string $startTime
     * @param string $endTime
     *
     * @return $this
     */
    public function between(string $startTime, string $endTime): self
    {
        return $this->when($this->inTimeInterval($startTime, $endTime));
    }

    /**
     * Schedule the event to not run between start and end time.
     *
     * @param string $startTime
     * @param string $endTime
     *
     * @return $this
     */
    public function unlessBetween(string $startTime, string $endTime): self
    {
        return $this->skip($this->inTimeInterval($startTime, $endTime));
    }

    /**
     * Schedule the event to run between start and end time.
     *
     * @param string $startTime
     * @param string $endTime
     *
     * @return Closure
     */
    private function inTimeInterval(string $startTime, string $endTime): Closure
    {
        [$now, $startTime, $endTime] = [
            Carbon::now($this->timezone),
            Carbon::parse($startTime, $this->timezone),
            Carbon::parse($endTime, $this->timezone),
        ];

        if ($endTime->lessThan($startTime)) {
            if ($startTime->greaterThan($now)) {
                $startTime = $startTime->subDay(1);
            } else {
                $endTime = $endTime->addDay(1);
            }
        }

        return function () use ($now, $startTime, $endTime) {
            return $now->between($startTime, $endTime);
        };
    }

    /**
     * Schedule the event to run every minute.
     *
     * @return $this
     */
    public function everyMinute(): self
    {
        return $this->spliceIntoPosition(1, '*');
    }

    /**
     * Schedule the event to run every two minutes.
     *
     * @return $this
     */
    public function everyTwoMinutes(): self
    {
        return $this->spliceIntoPosition(1, '*/2');
    }

    /**
     * Schedule the event to run every three minutes.
     *
     * @return $this
     */
    public function everyThreeMinutes(): self
    {
        return $this->spliceIntoPosition(1, '*/3');
    }

    /**
     * Schedule the event to run every four minutes.
     *
     * @return $this
     */
    public function everyFourMinutes(): self
    {
        return $this->spliceIntoPosition(1, '*/4');
    }

    /**
     * Schedule the event to run every five minutes.
     *
     * @return $this
     */
    public function everyFiveMinutes(): self
    {
        return $this->spliceIntoPosition(1, '*/5');
    }

    /**
     * Schedule the event to run every ten minutes.
     *
     * @return $this
     */
    public function everyTenMinutes(): self
    {
        return $this->spliceIntoPosition(1, '*/10');
    }

    /**
     * Schedule the event to run every fifteen minutes.
     *
     * @return $this
     */
    public function everyFifteenMinutes(): self
    {
        return $this->spliceIntoPosition(1, '*/15');
    }

    /**
     * Schedule the event to run every thirty minutes.
     *
     * @return $this
     */
    public function everyThirtyMinutes(): self
    {
        return $this->spliceIntoPosition(1, '0,30');
    }

    /**
     * Schedule the event to run hourly.
     *
     * @return $this
     */
    public function hourly(): self
    {
        return $this->spliceIntoPosition(1, 0);
    }

    /**
     * Schedule the event to run hourly at a given offset in the hour.
     *
     * @param array|int $offset
     *
     * @return $this
     */
    public function hourlyAt($offset): self
    {
        $offset = is_array($offset) ? implode(',', $offset) : $offset;

        return $this->spliceIntoPosition(1, $offset);
    }

    /**
     * Schedule the event to run every two hours.
     *
     * @return $this
     */
    public function everyTwoHours(): self
    {
        return $this->spliceIntoPosition(1, 0)
                    ->spliceIntoPosition(2, '*/2')
        ;
    }

    /**
     * Schedule the event to run every three hours.
     *
     * @return $this
     */
    public function everyThreeHours(): self
    {
        return $this->spliceIntoPosition(1, 0)
                    ->spliceIntoPosition(2, '*/3')
        ;
    }

    /**
     * Schedule the event to run every four hours.
     *
     * @return $this
     */
    public function everyFourHours(): self
    {
        return $this->spliceIntoPosition(1, 0)
                    ->spliceIntoPosition(2, '*/4')
        ;
    }

    /**
     * Schedule the event to run every six hours.
     *
     * @return $this
     */
    public function everySixHours(): self
    {
        return $this->spliceIntoPosition(1, 0)
                    ->spliceIntoPosition(2, '*/6')
        ;
    }

    /**
     * Schedule the event to run daily.
     *
     * @return $this
     */
    public function daily(): self
    {
        return $this->spliceIntoPosition(1, 0)
                    ->spliceIntoPosition(2, 0)
        ;
    }

    /**
     * Schedule the command at a given time.
     *
     * @param string $time
     *
     * @return $this
     */
    public function at(string $time): self
    {
        return $this->dailyAt($time);
    }

    /**
     * Schedule the event to run daily at a given time (10:00, 19:30, etc).
     *
     * @param string $time
     *
     * @return $this
     */
    public function dailyAt(string $time): self
    {
        $segments = explode(':', $time);

        return $this->spliceIntoPosition(2, (int)$segments[0])
                    ->spliceIntoPosition(1, count($segments) === 2 ? (int)$segments[1] : '0')
        ;
    }

    /**
     * Schedule the event to run twice daily.
     *
     * @param int $first
     * @param int $second
     *
     * @return $this
     */
    public function twiceDaily(int $first = 1, int $second = 13): self
    {
        return $this->twiceDailyAt($first, $second, 0);
    }

    /**
     * Schedule the event to run twice daily at a given offset.
     *
     * @param int $first
     * @param int $second
     * @param int $offset
     *
     * @return $this
     */
    public function twiceDailyAt(int $first = 1, int $second = 13, int $offset = 0): self
    {
        $hours = $first . ',' . $second;

        return $this->spliceIntoPosition(1, $offset)
                    ->spliceIntoPosition(2, $hours)
        ;
    }

    /**
     * Schedule the event to run only on weekdays.
     *
     * @return $this
     */
    public function weekdays(): self
    {
        return $this->days(Schedule::MONDAY . '-' . Schedule::FRIDAY);
    }

    /**
     * Schedule the event to run only on weekends.
     *
     * @return $this
     */
    public function weekends(): self
    {
        return $this->days(Schedule::SATURDAY . ',' . Schedule::SUNDAY);
    }

    /**
     * Schedule the event to run only on Mondays.
     *
     * @return $this
     */
    public function mondays(): self
    {
        return $this->days(Schedule::MONDAY);
    }

    /**
     * Schedule the event to run only on Tuesdays.
     *
     * @return $this
     */
    public function tuesdays(): self
    {
        return $this->days(Schedule::TUESDAY);
    }

    /**
     * Schedule the event to run only on Wednesdays.
     *
     * @return $this
     */
    public function wednesdays(): self
    {
        return $this->days(Schedule::WEDNESDAY);
    }

    /**
     * Schedule the event to run only on Thursdays.
     *
     * @return $this
     */
    public function thursdays(): self
    {
        return $this->days(Schedule::THURSDAY);
    }

    /**
     * Schedule the event to run only on Fridays.
     *
     * @return $this
     */
    public function fridays(): self
    {
        return $this->days(Schedule::FRIDAY);
    }

    /**
     * Schedule the event to run only on Saturdays.
     *
     * @return $this
     */
    public function saturdays(): self
    {
        return $this->days(Schedule::SATURDAY);
    }

    /**
     * Schedule the event to run only on Sundays.
     *
     * @return $this
     */
    public function sundays(): self
    {
        return $this->days(Schedule::SUNDAY);
    }

    /**
     * Schedule the event to run weekly.
     *
     * @return $this
     */
    public function weekly(): self
    {
        return $this->spliceIntoPosition(1, 0)
                    ->spliceIntoPosition(2, 0)
                    ->spliceIntoPosition(5, 0)
        ;
    }

    /**
     * Schedule the event to run weekly on a given day and time.
     *
     * @param array|mixed $dayOfWeek
     * @param string      $time
     *
     * @return $this
     */
    public function weeklyOn($dayOfWeek, string $time = '0:0'): self
    {
        $this->dailyAt($time);

        return $this->days($dayOfWeek);
    }

    /**
     * Schedule the event to run monthly.
     *
     * @return $this
     */
    public function monthly(): self
    {
        return $this->spliceIntoPosition(1, 0)
                    ->spliceIntoPosition(2, 0)
                    ->spliceIntoPosition(3, 1)
        ;
    }

    /**
     * Schedule the event to run monthly on a given day and time.
     *
     * @param int    $dayOfMonth
     * @param string $time
     *
     * @return $this
     */
    public function monthlyOn(int $dayOfMonth = 1, string $time = '0:0'): self
    {
        $this->dailyAt($time);

        return $this->spliceIntoPosition(3, $dayOfMonth);
    }

    /**
     * Schedule the event to run twice monthly at a given time.
     *
     * @param int    $first
     * @param int    $second
     * @param string $time
     *
     * @return $this
     */
    public function twiceMonthly(int $first = 1, int $second = 16, string $time = '0:0'): self
    {
        $daysOfMonth = $first . ',' . $second;

        $this->dailyAt($time);

        return $this->spliceIntoPosition(3, $daysOfMonth);
    }

    /**
     * Schedule the event to run on the last day of the month.
     *
     * @param string $time
     *
     * @return $this
     */
    public function lastDayOfMonth(string $time = '0:0'): self
    {
        $this->dailyAt($time);

        return $this->spliceIntoPosition(3, Carbon::now()->endOfMonth()->day);
    }

    /**
     * Schedule the event to run quarterly.
     *
     * @return $this
     */
    public function quarterly(): self
    {
        return $this->spliceIntoPosition(1, 0)
                    ->spliceIntoPosition(2, 0)
                    ->spliceIntoPosition(3, 1)
                    ->spliceIntoPosition(4, '1-12/3')
        ;
    }

    /**
     * Schedule the event to run yearly.
     *
     * @return $this
     */
    public function yearly(): self
    {
        return $this->spliceIntoPosition(1, 0)
                    ->spliceIntoPosition(2, 0)
                    ->spliceIntoPosition(3, 1)
                    ->spliceIntoPosition(4, 1)
        ;
    }

    /**
     * Schedule the event to run yearly on a given month, day, and time.
     *
     * @param int        $month
     * @param int|string $dayOfMonth
     * @param string     $time
     *
     * @return $this
     */
    public function yearlyOn(int $month = 1, $dayOfMonth = 1, string $time = '0:0'): self
    {
        $this->dailyAt($time);

        return $this->spliceIntoPosition(3, $dayOfMonth)
                    ->spliceIntoPosition(4, $month)
        ;
    }

    /**
     * Set the days of the week the command should run on.
     *
     * @param array|mixed $days
     *
     * @return $this
     */
    public function days($days): self
    {
        $days = is_array($days) ? $days : func_get_args();

        return $this->spliceIntoPosition(5, implode(',', $days));
    }

    /**
     * Set the timezone the date should be evaluated on.
     *
     * @param DateTimeZone|string $timezone
     *
     * @return $this
     */
    public function timezone($timezone): self
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * Splice the given value into the given position of the expression.
     *
     * @param int    $position
     * @param string $value
     *
     * @return $this
     */
    protected function spliceIntoPosition(int $position, string $value): self
    {
        $segments = explode(' ', $this->expression);

        $segments[$position - 1] = $value;

        return $this->cron(implode(' ', $segments));
    }
}

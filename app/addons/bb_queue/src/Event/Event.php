<?php

namespace Tygh\Addons\Queue\Event;

use Carbon\Carbon;
use DateTimeZone;
use Throwable;
use Tygh\Addons\Queue\CronExpressionParser\Expression as CronExpression;

class Event
{
    use ManagesFrequencies;

    /**
     * The command string.
     *
     * @var string
     */
    public string $command;

    /**
     * The cron expression representing the event's frequency.
     *
     * @var string
     */
    public string $expression = '* * * * *';

    /**
     * The timezone the date should be evaluated on.
     *
     * @var DateTimeZone|string
     */
    public $timezone;

    /**
     * The array of filter callbacks.
     *
     * @var array
     */
    protected array $filters = [];

    /**
     * The human readable description of the event.
     *
     * @var string
     */
    public string $description;

    /**
     * The exit status code of the command.
     *
     * @var int|null
     */
    public ?int $exitCode;

    /**
     * The callback to call.
     *
     * @var string
     */
    protected $callback;

    /**
     * The parameters to pass to the method.
     *
     * @var array
     */
    protected array $parameters;

    /**
     * The result of the callback's execution.
     *
     * @var mixed
     */
    protected $result;

    /**
     * The exception that was thrown when calling the callback, if any.
     *
     * @var Throwable|null
     */
    protected ?Throwable $exception;

    /**
     * Create a new event instance.
     *
     * @param string|callable           $callback
     * @param array                     $parameters
     * @param \DateTimeZone|string|null $timezone
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($callback, array $parameters = [], $timezone = null)
    {
        $this->callback   = $callback;
        $this->parameters = $parameters;
        $this->timezone   = $timezone;
    }

    /**
     * Run the callback.
     *
     * @return int
     */
    public function run(): int
    {
        try {
            $this->result = is_object($this->callback)
                ? call_user_func_array([$this->callback, '__invoke'], $this->parameters)
                : call_user_func_array($this->callback, $this->parameters);

            return $this->result === false ? 1 : 0;
        } catch (Throwable $e) {
            $this->exception = $e;
            return 1;
        }
    }


    /**
     * Determine if the given event should run based on the Cron expression.
     *
     * @return bool
     */
    public function isDue(): bool
    {
        return $this->expressionPasses();
    }

    /**
     * Determine if the filters pass for the event.
     *
     * @return bool
     */
    public function filtersPass(): bool
    {
        foreach ($this->filters as $callback) {
            if (!$callback()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if the Cron expression passes.
     *
     * @return bool
     */
    protected function expressionPasses(): bool
    {
        $date = Carbon::now();

        if ($this->timezone) {
            $date = $date->setTimezone($this->timezone);
        }

        return (new CronExpression())->isCronDue($this->expression, $date);
    }

    /**
     * Set the human-friendly description of the event.
     *
     * @param string $description
     *
     * @return $this
     */
    public function name(string $description): self
    {
        return $this->description($description);
    }

    /**
     * Set the human-friendly description of the event.
     *
     * @param string $description
     *
     * @return $this
     */
    public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }
}

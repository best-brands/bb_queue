<?php

namespace Tygh\Addons\Queue;

use Exception;

/**
 * When a job can not be handled and should be rescheduled.
 */
class JobException extends Exception
{
}

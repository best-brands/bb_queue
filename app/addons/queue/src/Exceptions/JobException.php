<?php

namespace Tygh\Addons\Queue\Exceptions;

use Exception;

/**
 * When a job can not be handled and should be rescheduled.
 */
class JobException extends Exception
{
}

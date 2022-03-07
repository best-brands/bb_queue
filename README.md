# Queue

An advanced add-on that allows you to write jobs that get processed by a worker-pool.

## Requirements.

Mind that this requires CsCart 4.15 or higher. This has to do with a number of patches that have been put in place to
allow better programming principles. Such as the addition of transactions to the `Tygh\Database\Connection` class, and
a change to how hooks are prioritized (they now prioritize Pimple, not whether they are 'callable' or not). Furthermore,
PHP 7.4 is required. This has to do with the simply better typing system available.

## Useful add-ons

### Email queue

Allows sending emails in a delayed fashion. Emails will get put on the queue by default allowing them to be processed
in the background. This results in much faster order updates, sending of newsletters and provides overall a better
customer experience.

## Creating a new job

Jobs are serialized when they are put on a queue. In short if you plan to add any distinguishable properties to a job,
they will be kept. However, mind that if you put very big properties in your constructor, that it might suffer because
the job could end up being too large.

The `handle` function does not auto-wire arguments from Pimple, as this is simply not possible for now. This is because
Pimple does not support auto-wiring and additionally has now way of invoking methods on objects through the DI like
Illuminate's `->call` function.

```php
namespace Tygh\Addons\QueueExample\Jobs;

use Tygh\Addons\Queue\InteractsWithQueue;
use Tygh\Addons\Queue\Queueable;
use Tygh\Addons\Queue\ShouldQueue;

class ExampleJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue;

    protected string $message;

    public function __construct(string $message) {
        $this->message = $message;
    }

    public function handle(): void {
        echo $this->message;
    }
}

```

## Scheduling jobs through cron

Use the latest V4 structure for CsCart add-ons and register use ServiceProviders. In this service provider you will be
able to define the scheduler behavior by means of extending the Schedule dependency. In the queue_example add-on we do
the following:

```php
namespace Tygh\Addons\QueueExample;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tygh\Addons\Queue\Schedule;

class ServiceProvider implements ServiceProviderInterface
{
    public function register(Container $pimple)
    {
        // We define the job dependency, note that we should use a factory to always instantiate new objects.
        $pimple[Jobs\ExampleJob::class] = $pimple->factory(fn() => new Jobs\ExampleJob("default"));

        $pimple->extend(Schedule::class, function (Schedule $schedule) {
            // By passing a FQCN we retrieve it via our DI.
            $schedule->job(Jobs\ExampleJob::class)->dailyAt('9:30');

            // We can also do direct invocations through instantiating the object.
            $schedule->job(new Jobs\ExampleJob("Direct invocation!"));
        });
    }
}

```

## What to do with cli.php?

It is recommended to put cli.php outside the root folder, so you end up with a structure like this:

```
%%STORE_PATH%%:
├─ cscart    # Contains the cscart installation folder
│  ├─ app    # Application folder
│  ├─ design # Theming folder
│  ├─ ...
├─ cli.php      # A simple script that sets the workers accordingly.
```

If you do not, you **should change the contents to include init.php accordingly**.

## Configuring the Queue add-on

Configure the crontab to schedule your periodic jobs accordingly:

```crontab
* * * * * /usr/bin/php7.4 -f /var/www/cli.php -- --dispatch=queue.schedule_cron_jobs
```

Configure supervisor with the amount of workers you will need.

```conf
[supervisord]
nodaemon = true
logfile = /var/log/supervisord.log
pidfile = /run/supervisord.pid

[program:cscart-worker]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php7.4 -f /var/www/cli.php -- --dispatch=queue.launch_worker
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/log/worker.log
stopwaitsecs=3600
```

Of course these configuration options should be altered according to your needs, such as the location of the PHP binary,
the amount of processes that you need to be 'working', logging output, etc.

## Suppressing the default CsCart logger

You can suppress the default CsCart logger by altering `functions/fn.log.php`;
Make sure to replace the `fn_set_hook('save_log', ...);` with the following:

```php
    $suppress = false;

    fn_set_hook('save_log', $type, $action, $data, $user_id, $content, $event_type, $object_primary_keys, $suppress);

    if ($suppress) {
        return true;
    }
```

## Dispatching jobs

You can dispatch jobs using `fn_queue_dispatch`. To dispatch the aforementioned `ExampleJob` you could do the following:

```php
namespace Tygh\Addons\QueueExample;

fn_queue_dispatch(new Jobs\ExampleJob("Scheduling through fn_queue_dispatch!"));
```

<?php

use Tygh\Addons\Queue\Failed\FailedJobProviderInterface;
use Tygh\Addons\Queue\Manager;
use Tygh\Addons\Queue\Schedule;
use Tygh\Addons\Queue\Worker;
use Tygh\Addons\Queue\WorkerOptions;
use Tygh\Enum\NotificationSeverity;
use Tygh\Http;
use Tygh\Tygh;

if (php_sapi_name() === 'cli') {
    if ($mode === 'launch_worker') {
        $worker = Tygh::$app[Worker::class];
        $worker->daemon(
            Tygh::$app['addons.queue.config']['default'] ?? 'default',
            Tygh::$app['addons.queue.config']['worker']['queues'] ?? 'default',
            Tygh::$app[WorkerOptions::class]
        );

    } else if ($mode === 'schedule_cron_jobs') {
        $schedule = Tygh::$app[Schedule::class];

        foreach ($schedule->dueEvents() as $due_event) {
            $due_event->run();
        }
    }

    exit(0);
}

$jobs_manager = Tygh::$app[Manager::class];
$failed_jobs_provider = Tygh::$app[FailedJobProviderInterface::class];

if ($_SERVER['REQUEST_METHOD'] === Http::POST) {
    if ($mode === 'm_jobs_failed_delete') {
        $jobs_to_delete = (array)$_REQUEST['job_ids'] ?? [];

        if (!empty($jobs_to_delete)) {
            $failed_jobs_provider->forget($jobs_to_delete);
        }

        return [CONTROLLER_STATUS_REDIRECT, 'queue.jobs_failed'];

    } else if ($mode === 'm_jobs_failed_reschedule') {
        $jobs_to_reschedule = (array)$_REQUEST['job_ids'] ?? [];

        if (empty($jobs_to_reschedule)) {
            return [CONTROLLER_STATUS_REDIRECT, 'queue.jobs_failed'];
        }

        [$jobs_to_reschedule] = $failed_jobs_provider->all([
            'items_per_page' => 0,
            'job_ids'        => $jobs_to_reschedule,
        ]);

        foreach ($jobs_to_reschedule as $job) {
            try {
                $jobs_manager->connection($job['connection'])->pushRaw($job['payload'], $job['queue']);
                $failed_jobs_provider->forget($job['id']);
            } catch (Throwable $e) {
                fn_set_notification(
                    NotificationSeverity::ERROR,
                    __('queue.reschedule_failed'),
                    $e->getMessage(),
                );
            }
        }

        return [CONTROLLER_STATUS_REDIRECT, 'queue.jobs_failed'];
    }
}

if ($mode === 'jobs_failed') {
    [$jobs_failed, $params] = $failed_jobs_provider->all(array_merge($_REQUEST, [
        'decode_payload' => true,
    ]));

    Tygh::$app['view']->assign('jobs_failed', $jobs_failed);
    Tygh::$app['view']->assign('search', $params);

} else if ($mode === 'jobs_failed_prune') {
    $failed_jobs_provider->flush();
    return [CONTROLLER_STATUS_REDIRECT, 'queue.jobs_failed'];
}

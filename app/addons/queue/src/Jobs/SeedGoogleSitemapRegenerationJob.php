<?php

namespace Tygh\Addons\Queue\Jobs;

use Tygh\Addons\Queue\Connectors\ConnectorInterface;
use Tygh\Addons\Queue\Job;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\StorefrontStatuses;
use Tygh\Storefront\Repository;

/**
 * Simple job to seed the google sitemap regeneration jobs.
 */
class SeedGoogleSitemapRegenerationJob extends Job
{
    protected bool $is_unique = true;

    /** @var string|null */
//    protected ?string $cron_expression = '* * * * *';

    /** @var Repository */
    protected Repository $storefronts;

    /**
     * @param ConnectorInterface $connector
     * @param array              $storefronts
     */
    public function __construct(ConnectorInterface $connector, Repository $storefronts)
    {
        parent::__construct($connector);
        $this->storefronts = $storefronts;
    }

    /**
     * @param array $job_info
     * @param mixed $message
     */
    public function handle(array $job_info, $message): void
    {
        $allowed_statuses = [StorefrontStatuses::OPEN];

        if (defined('DEVELOPMENT')) {
            $allowed_statuses[] = StorefrontStatuses::CLOSED;
        }

        [$storefronts] = $this->storefronts->find([
            'status' => $allowed_statuses
        ]);

        foreach ($storefronts as $storefront) {
            fn_queue_dispatch(ExportGoogleSitemapFeedsJob::class, $storefront->storefront_id);
        }
    }
}

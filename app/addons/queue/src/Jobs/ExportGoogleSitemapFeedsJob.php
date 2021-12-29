<?php

namespace Tygh\Addons\Queue\Jobs;

use Tygh\Addons\Queue\Job;

class ExportGoogleSitemapFeedsJob extends Job
{
    public function handle(array $job_info, $message): void
    {
        $storefront_id = intval($message);
        fn_google_sitemap_get_content([$storefront_id]);
    }
}

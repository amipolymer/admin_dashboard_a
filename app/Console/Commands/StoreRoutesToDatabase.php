<?php
namespace App\Console\Commands;

use App\Support\RouteRegistrySync;
use Illuminate\Console\Command;

class StoreRoutesToDatabase extends Command
{
    // php artisan route:store
    protected $signature = 'routes:store';

    protected $description = 'Store all route URLs into the database';

    public function handle()
    {
        $result = RouteRegistrySync::syncFromApplication();

        $this->info(sprintf(
            'Created %d new route(s), %d already in DB. Total in route_u_r_l_lists: %d.',
            $result['created'],
            $result['skipped'],
            $result['total_in_db']
        ));
    }
}

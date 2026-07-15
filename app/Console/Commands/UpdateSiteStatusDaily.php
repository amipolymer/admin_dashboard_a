<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
// use App\Models\SiteDetail;
// use App\Models\DailyLabourEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateSiteStatusDaily extends Command
{
    protected $signature = 'site:update-status-daily';
    protected $description = 'Update site status to unupdated if no entry exists for today';

    public function handle()
    {
        // Log::info('✅ site:update-status-daily ran at: ' . now());
        // $today = Carbon::today()->toDateString();

        // $sites = SiteDetail::where('status', 'active')
        //     ->whereDate('start_date', '<=', $today)
        //     ->get();

        // foreach ($sites as $site) {
        //     $hasEntry = $site->dailyLabourEntries()
        //         ->whereDate('plan_of_date', $today)
        //         ->exists();

        //     if ($hasEntry) {
        //         // If previously marked as unupdated, restore to active
        //         if ($site->status === 'unupdated') {
        //             $site->status = 'active';
        //             $site->save();
        //             $this->info("✅ Site ID {$site->id} updated back to ACTIVE.");
        //         }
        //     } else {
        //         // Mark site as unupdated
        //         $site->status = 'unupdated';
        //         $site->save();

        //         // Only create entry if it doesn’t exist
        //         $existing = $site->dailyLabourEntries()
        //             ->whereDate('plan_of_date', $today)
        //             ->where('daily_labour_status', 'unupdated')
        //             ->first();

        //         if (!$existing) {
        //             DailyLabourEntry::create([
        //                 'site_id'             => $site->id,
        //                 'plan_of_date'        => $today,
        //                 'plan_message'        => 'Site Remark not update today',
        //                 'labour_data'         => null,
        //                 'added_by'            => 1,
        //                 'daily_labour_status' => 'unupdated',
        //             ]);

        //             $this->info("⚠️ Site ID {$site->id} marked as UNUPDATED and entry created.");
        //         } else {
        //             $this->warn("⚠️ Site ID {$site->id} already has an UNUPDATED entry.");
        //         }
        //     }
        // }

        // $this->info("✅ Site status update process completed for {$today}");
    }
}

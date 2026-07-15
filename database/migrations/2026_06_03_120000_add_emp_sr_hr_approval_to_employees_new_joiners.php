<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees_new_joiners', function (Blueprint $table) {
            if (!Schema::hasColumn('employees_new_joiners', 'emp_sr_hr_approval')) {
                $table->json('emp_sr_hr_approval')->nullable()->after('emp_other');
            }
            if (!Schema::hasColumn('employees_new_joiners', 'emp_offer_sr_hr_status')) {
                $table->string('emp_offer_sr_hr_status', 20)->nullable()->index()->after('emp_sr_hr_approval');
            }
            if (!Schema::hasColumn('employees_new_joiners', 'emp_appointment_sr_hr_status')) {
                $table->string('emp_appointment_sr_hr_status', 20)->nullable()->index()->after('emp_offer_sr_hr_status');
            }
        });

        // Copy legacy data from emp_other.sr_hr_approvals → emp_sr_hr_approval
        DB::table('employees_new_joiners')
            ->whereNotNull('emp_other')
            ->orderBy('id')
            ->chunk(100, function ($rows) {
                foreach ($rows as $row) {
                    $other = json_decode($row->emp_other, true);
                    if (!is_array($other) || empty($other['sr_hr_approvals'])) {
                        continue;
                    }

                    $approvals = $other['sr_hr_approvals'];
                    DB::table('employees_new_joiners')->where('id', $row->id)->update([
                        'emp_sr_hr_approval' => json_encode($approvals),
                        'emp_offer_sr_hr_status' => $approvals['offer']['status'] ?? null,
                        'emp_appointment_sr_hr_status' => $approvals['appointment']['status'] ?? null,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('employees_new_joiners', function (Blueprint $table) {
            foreach (['emp_sr_hr_approval', 'emp_offer_sr_hr_status', 'emp_appointment_sr_hr_status'] as $col) {
                if (Schema::hasColumn('employees_new_joiners', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

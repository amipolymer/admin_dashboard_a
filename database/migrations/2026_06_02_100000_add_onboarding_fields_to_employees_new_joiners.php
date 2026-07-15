<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees_new_joiners', function (Blueprint $table) {
            if (!Schema::hasColumn('employees_new_joiners', 'emp_onboarding_step')) {
                $table->string('emp_onboarding_step', 50)->default('start')->after('emp_status');
            }
            if (!Schema::hasColumn('employees_new_joiners', 'emp_onboarding_status')) {
                $table->enum('emp_onboarding_status', ['active', 'on_hold', 'completed'])->default('active')->after('emp_onboarding_step');
            }
            if (!Schema::hasColumn('employees_new_joiners', 'emp_profile_data')) {
                $table->json('emp_profile_data')->nullable()->after('emp_other');
            }
            if (!Schema::hasColumn('employees_new_joiners', 'emp_signature')) {
                $table->longText('emp_signature')->nullable();
            }
            if (!Schema::hasColumn('employees_new_joiners', 'emp_employee_id')) {
                $table->string('emp_employee_id', 50)->nullable();
            }
            if (!Schema::hasColumn('employees_new_joiners', 'emp_joining_date')) {
                $table->date('emp_joining_date')->nullable();
            }
            if (!Schema::hasColumn('employees_new_joiners', 'emp_joining_requirements')) {
                $table->json('emp_joining_requirements')->nullable();
            }
            if (!Schema::hasColumn('employees_new_joiners', 'emp_policy_accepted_at')) {
                $table->timestamp('emp_policy_accepted_at')->nullable();
            }
            if (!Schema::hasColumn('employees_new_joiners', 'emp_policy_signature')) {
                $table->longText('emp_policy_signature')->nullable();
            }
            if (!Schema::hasColumn('employees_new_joiners', 'emp_offer_sent_at')) {
                $table->timestamp('emp_offer_sent_at')->nullable();
            }
            if (!Schema::hasColumn('employees_new_joiners', 'emp_registration_sent_at')) {
                $table->timestamp('emp_registration_sent_at')->nullable();
            }
            if (!Schema::hasColumn('employees_new_joiners', 'emp_appointment_sent_at')) {
                $table->timestamp('emp_appointment_sent_at')->nullable();
            }
            if (!Schema::hasColumn('employees_new_joiners', 'emp_appointment_due_date')) {
                $table->date('emp_appointment_due_date')->nullable();
            }
            if (!Schema::hasColumn('employees_new_joiners', 'emp_archived_at')) {
                $table->timestamp('emp_archived_at')->nullable();
            }
            if (!Schema::hasColumn('employees_new_joiners', 'emp_offer_reject_reason')) {
                $table->text('emp_offer_reject_reason')->nullable();
            }
            if (!Schema::hasColumn('employees_new_joiners', 'emp_appointment_reject_reason')) {
                $table->text('emp_appointment_reject_reason')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees_new_joiners', function (Blueprint $table) {
            $columns = [
                'emp_onboarding_step',
                'emp_onboarding_status',
                'emp_profile_data',
                'emp_signature',
                'emp_employee_id',
                'emp_joining_date',
                'emp_joining_requirements',
                'emp_policy_accepted_at',
                'emp_policy_signature',
                'emp_offer_sent_at',
                'emp_registration_sent_at',
                'emp_appointment_sent_at',
                'emp_appointment_due_date',
                'emp_archived_at',
                'emp_offer_reject_reason',
                'emp_appointment_reject_reason',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('employees_new_joiners', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

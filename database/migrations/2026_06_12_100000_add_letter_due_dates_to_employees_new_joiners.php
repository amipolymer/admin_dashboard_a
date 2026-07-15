<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees_new_joiners', function (Blueprint $table) {
            if (!Schema::hasColumn('employees_new_joiners', 'emp_offer_due_date')) {
                $table->date('emp_offer_due_date')->nullable()->after('emp_offer_sent_at');
            }
            if (!Schema::hasColumn('employees_new_joiners', 'emp_registration_due_date')) {
                $table->date('emp_registration_due_date')->nullable()->after('emp_registration_sent_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees_new_joiners', function (Blueprint $table) {
            foreach (['emp_offer_due_date', 'emp_registration_due_date'] as $column) {
                if (Schema::hasColumn('employees_new_joiners', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

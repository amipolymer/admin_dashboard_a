<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees_new_joiners', function (Blueprint $table) {
            foreach (['emp_application_source_detail', 'emp_ongrid_profession_id'] as $col) {
                if (Schema::hasColumn('employees_new_joiners', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees_new_joiners', function (Blueprint $table) {
            if (!Schema::hasColumn('employees_new_joiners', 'emp_ongrid_profession_id')) {
                $table->string('emp_ongrid_profession_id', 20)->nullable()->after('emp_role');
            }
            if (!Schema::hasColumn('employees_new_joiners', 'emp_application_source_detail')) {
                $table->string('emp_application_source_detail', 255)->nullable()->after('emp_application_source');
            }
        });
    }
};

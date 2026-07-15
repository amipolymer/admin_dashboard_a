<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('onboarding_step_logs');

        Schema::table('employees_new_joiners', function (Blueprint $table) {
            foreach (['emp_registration_file', 'emp_registration_file_path'] as $column) {
                if (Schema::hasColumn('employees_new_joiners', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('onboarding_step_logs')) {
            Schema::create('onboarding_step_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('emp_id');
                $table->string('from_step', 50)->nullable();
                $table->string('to_step', 50);
                $table->string('actor', 100)->nullable();
                $table->text('remark')->nullable();
                $table->timestamps();
                $table->index('emp_id');
            });
        }

        Schema::table('employees_new_joiners', function (Blueprint $table) {
            if (!Schema::hasColumn('employees_new_joiners', 'emp_registration_file')) {
                $table->string('emp_registration_file')->nullable();
            }
            if (!Schema::hasColumn('employees_new_joiners', 'emp_registration_file_path')) {
                $table->string('emp_registration_file_path')->nullable();
            }
        });
    }
};

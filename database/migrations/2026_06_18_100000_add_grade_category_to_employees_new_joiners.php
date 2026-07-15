<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees_new_joiners', function (Blueprint $table) {
            $table->string('emp_grade', 32)->nullable()->after('emp_role');
            $table->string('emp_category', 64)->nullable()->after('emp_grade');
        });
    }

    public function down(): void
    {
        Schema::table('employees_new_joiners', function (Blueprint $table) {
            $table->dropColumn(['emp_grade', 'emp_category']);
        });
    }
};

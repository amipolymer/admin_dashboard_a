<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('annual_report_view_forms', function (Blueprint $table) {
            $table->id();
            $table->string('full_name', 100);
            $table->string('email', 100);
            $table->string('company_name', 100);
            $table->string('mobile', 15);
            $table->longText('location')->nullable();
            $table->char('gst_no', 15)->nullable();
            $table->string('department', 50)->nullable();
            $table->string('report_year', 100)->default('2025-2026');
            $table->enum('status', ['process', 'approved', 'reject'])->default('process');
            $table->string('approved_by', 100)->nullable();
            $table->dateTime('approve_disapprove_date')->nullable();
            $table->longText('remark')->nullable();
            $table->dateTime('view_date')->nullable();
            $table->string('viewed_by', 100)->nullable();
            $table->string('user_ip', 100)->nullable();
            $table->string('URL_view', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('annual_report_view_forms');
    }
};

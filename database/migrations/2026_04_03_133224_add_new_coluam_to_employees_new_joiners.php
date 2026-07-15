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
        Schema::table('employees_new_joiners', function (Blueprint $table) {
                $table->string('emp_department')->nullable();
                $table->date('emp_dob')->nullable();
                $table->string('emp_mrf_no')->nullable();
                $table->enum('emp_offer_letter_status', ['pending', 'accept', 'reject','process','0'])->default('0');
                $table->enum('emp_appointment_letter_status', ['pending', 'accept', 'reject','process','0'])->default('0');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees_new_joiners', function (Blueprint $table) {
            $table->dropColumn(['emp_department', 'emp_dob', 'emp_mrf_no', 'emp_offer_letter_status', 'emp_appointment_letter_status']);
        });
    }
};

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
        Schema::create('employees_new_joiners', function (Blueprint $table) {
            $table->id();
            $table->string('emp_name')->nullable();
            $table->string('emp_email')->unique()->nullable();
            $table->string('emp_phone')->unique()->nullable();
            $table->string('emp_role')->nullable();
            $table->string('emp_location')->nullable();
            $table->string('emp_hr_id')->nullable();
            $table->date('emp_date')->nullable();
            $table->date('emp_document_due_date')->nullable();
            $table->string('emp_status')->nullable();
            $table->string('emp_url')->unique()->nullable();
            $table->string('emp_folder')->unique()->nullable();
            $table->string('emp_folder_path')->unique()->nullable();
            $table->string('emergency_contact')->unique()->nullable();
            $table->enum('emp_document_status', ['process', 'completed', 'rejected'])->default('process')->index();
            $table->timestamp('emp_last_updated_at')->nullable();
            $table->longText('emp_other')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees_new_joiners');
    }
};
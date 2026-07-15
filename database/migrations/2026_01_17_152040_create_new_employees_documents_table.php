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
        Schema::create('new_employees_documents', function (Blueprint $table) {
        $table->id();
        // Employee reference
        $table->unsignedBigInteger('emp_id')->index();
        // Document date
        $table->date('emp_doc_date')->nullable();
        $table->string('emp_select_document')->nullable();
        $table->string('emp_document_file')->nullable();      
        $table->string('emp_document_file_path')->nullable();      
        // Overall status
        $table->enum('emp_document_status', ['upload', 'process', 'approved', 'rejected'])->default('upload')->index();
        // Approval details
        $table->unsignedBigInteger('emp_hr_id')->nullable();
        $table->timestamp('approval_date')->nullable();
        $table->text('rejection_reason')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('new_employees_documents');
    }
};
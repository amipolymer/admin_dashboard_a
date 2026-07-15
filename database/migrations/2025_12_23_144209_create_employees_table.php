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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('emp_id')->unique()->comment('Unique employee ID');
            $table->string('name')->nullable();
            $table->string('role')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('password')->unique()->nullable();
            $table->enum('login_access', ['no', 'yes'])->default('no');
            $table->string('phoneno')->nullable();
            $table->string('status')->default('active'); // Assuming status can be 'active
            $table->string('addedBy')->default('1')->comment('Added by (user name)');
            $table->longText('remark')->nullable();
            $table->string('delete_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};

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
        Schema::create('labours', function (Blueprint $table) {
            $table->id();
            $table->string('labour_id')->unique()->comment('Unique Labour ID');
            $table->string('name');
            $table->decimal('dailywage', 15, 2)->nullable()->comment('daily wage Value (₹)');
            $table->string('role')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('password')->unique()->nullable();
            $table->enum('login_access', ['no', 'yes'])->default('no');
            $table->string('phoneno')->nullable();
            $table->string('aadhar_no')->nullable();
            $table->string('status')->default('active'); // Assuming status can be 'active
            $table->string('addedBy')->default('1')->comment('Added by (user name)');
            $table->longText('remark')->nullable();
            $table->timestamps();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('labours');
    }
};

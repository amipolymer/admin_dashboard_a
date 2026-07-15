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
            Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('model'); // e.g., App\Models\User
            $table->unsignedBigInteger('model_id'); // e.g., 1
            $table->string('action'); // create | update | delete
            $table->json('old_data')->nullable();
            $table->json('new_data')->nullable();
            $table->string('status')->default('success');
            $table->unsignedBigInteger('performed_by')->nullable(); // user_id
            $table->string('ip_address')->nullable();
            $table->string('browser')->nullable();
            $table->string('platform')->nullable();
            $table->string('device')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};

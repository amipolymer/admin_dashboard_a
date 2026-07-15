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
        Schema::create('quick_links', function (Blueprint $table) {
            $table->id();
            $table->integer('srno')->default(0);
            $table->string('name');
            $table->string('url');
            $table->string('logo');
            $table->enum('openurl', ['same', 'new'])->default('same');
            $table->string('status')->default('active');
            $table->string('added_by')->default('admin');
            $table->string('delete_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quick_links');
    }
};

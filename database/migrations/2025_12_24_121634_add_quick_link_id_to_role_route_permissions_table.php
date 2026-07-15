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
        Schema::table('role_route_permissions', function (Blueprint $table) {
             $table->json('quick_link_id')->nullable(); // e.g. [1,3,5,6]
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('role_route_permissions', function (Blueprint $table) {
            //
        });
    }
};

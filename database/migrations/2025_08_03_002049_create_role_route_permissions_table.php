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
        Schema::create('role_route_permissions', function (Blueprint $table) {
             $table->id();
             $table->string('role_name');      // e.g. admin, emp, superadmin
             $table->json('url_ids')->nullable(); // e.g. [1,3,5,6]
             $table->enum('status', ['active', 'deactivate'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_route_permissions');
    }
};

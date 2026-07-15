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
            $table->string('ongrid_id')->nullable()->after('id');
            $table->json('ongrid_response')->nullable()->after('ongrid_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees_new_joiners', function (Blueprint $table) {
             $table->dropColumn(['ongird_id', 'response']);
        });
    }
};

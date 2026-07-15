<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('route_u_r_l_lists', function (Blueprint $table) {
            $table->index('url_name');
        });
    }

    public function down(): void
    {
        Schema::table('route_u_r_l_lists', function (Blueprint $table) {
            $table->dropIndex(['url_name']);
        });
    }
};

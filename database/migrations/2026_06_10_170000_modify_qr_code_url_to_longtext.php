<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boarding_passes', function (Blueprint $table) {
            $table->longText('qr_code_url')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('boarding_passes', function (Blueprint $table) {
            $table->string('qr_code_url')->nullable()->change();
        });
    }
};

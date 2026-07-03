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
        Schema::table('flights', function (Blueprint $table) {
            // Drop the old global unique constraint on flight_number
            $table->dropUnique('flights_flight_number_unique');
            
            // Add a new composite unique constraint: same flight number cannot exist on the exact same datetime
            $table->unique(['flight_number', 'departure_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flights', function (Blueprint $table) {
            // Revert changes
            $table->dropUnique(['flight_number', 'departure_time']);
            $table->unique('flight_number');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->enum('type', ['rental', 'purchase'])->default('rental')->after('status');
        });

        // Purchases have no rental period, so these become optional.
        Schema::table('reservations', function (Blueprint $table) {
            $table->date('event_start_date')->nullable()->change();
            $table->date('event_end_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->date('event_start_date')->nullable(false)->change();
            $table->date('event_end_date')->nullable(false)->change();
            $table->dropColumn('type');
        });
    }
};

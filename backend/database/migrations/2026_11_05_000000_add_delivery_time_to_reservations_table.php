<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The time the customer would like the furniture delivered, typed in the
    // quote form when they choose delivery to their address.
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('delivery_time', 5)->nullable()->after('delivery_date');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('delivery_time');
        });
    }
};

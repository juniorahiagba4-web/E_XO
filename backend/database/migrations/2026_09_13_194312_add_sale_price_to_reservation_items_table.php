<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservation_items', function (Blueprint $table) {
            $table->decimal('unit_sale_price', 12, 2)->nullable()->after('unit_price_per_day');
        });

        // A purchase line has a unit sale price instead of a daily rental rate.
        Schema::table('reservation_items', function (Blueprint $table) {
            $table->decimal('unit_price_per_day', 12, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('reservation_items', function (Blueprint $table) {
            $table->decimal('unit_price_per_day', 12, 2)->nullable(false)->change();
            $table->dropColumn('unit_sale_price');
        });
    }
};

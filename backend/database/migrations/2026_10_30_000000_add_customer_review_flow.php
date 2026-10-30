<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_reviews', function (Blueprint $table) {
            $table->foreignId('reservation_id')->nullable()->after('item_id')->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->after('reservation_id')->constrained()->nullOnDelete();
            $table->string('status')->default('pending')->after('comment');
            $table->unique(['reservation_id', 'item_id']);
        });

        // Reviews typed in by hand before this flow existed can't be traced back
        // to a real order, so they are kept for reference but no longer shown.
        DB::table('item_reviews')->update(['status' => 'archived']);

        Schema::table('reservations', function (Blueprint $table) {
            $table->string('review_token', 64)->nullable()->unique()->after('reference');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropUnique(['review_token']);
            $table->dropColumn('review_token');
        });

        Schema::table('item_reviews', function (Blueprint $table) {
            $table->dropUnique(['reservation_id', 'item_id']);
            $table->dropConstrainedForeignId('reservation_id');
            $table->dropConstrainedForeignId('customer_id');
            $table->dropColumn('status');
        });
    }
};

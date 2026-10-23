<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        Schema::table('settings', function (Blueprint $table) {
            if (! Schema::hasColumn('settings', 'allow_fake_reviews')) {
                $table->boolean('allow_fake_reviews')->default(false);
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('settings') && Schema::hasColumn('settings', 'allow_fake_reviews')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('allow_fake_reviews');
            });
        }
    }
};

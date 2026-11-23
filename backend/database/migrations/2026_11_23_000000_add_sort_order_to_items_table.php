<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Display order of the items on the website, set by drag and drop in the
     * admin. Existing items start in the order the site used so far
     * (category order, then name), so nothing moves on deployment.
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('category_id')->index();
        });

        $ids = DB::table('items')
            ->leftJoin('categories', 'categories.id', '=', 'items.category_id')
            ->orderByRaw('COALESCE(categories.sort_order, 999999)')
            ->orderByRaw('TRIM(items.name_fr)')
            ->pluck('items.id');

        foreach ($ids as $position => $id) {
            DB::table('items')->where('id', $id)->update(['sort_order' => $position + 1]);
        }
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['sort_order']);
            $table->dropColumn('sort_order');
        });
    }
};

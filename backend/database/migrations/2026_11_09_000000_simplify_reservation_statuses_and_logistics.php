<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // When the furniture is collected back after a rental.
            $table->string('return_time', 5)->nullable()->after('return_date');
            // A purchase has no event period, only the day it was bought.
            $table->date('purchase_date')->nullable()->after('event_end_date');
        });

        // The shop only uses four statuses now: "confirmed" already means the
        // customer has paid all or part, so the in-between ones fold into it.
        DB::table('reservations')->whereIn('status', ['deposit_paid', 'ongoing'])->update(['status' => 'confirmed']);
        DB::table('reservations')->where('status', 'draft')->update(['status' => 'quote_sent']);

        // Rentals delivered by the shop are collected back at the same time.
        DB::table('reservations')
            ->where('type', 'rental')
            ->where('delivery_method', 'delivery')
            ->whereNotNull('delivery_time')
            ->update(['return_time' => DB::raw('delivery_time')]);

        DB::table('reservations')->where('type', 'purchase')->orderBy('id')->each(function ($row) {
            DB::table('reservations')->where('id', $row->id)->update([
                'purchase_date' => substr((string) $row->created_at, 0, 10),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['return_time', 'purchase_date']);
        });
    }
};

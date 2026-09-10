<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_slot_templates', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->unsignedTinyInteger('weekday')->nullable()->comment('0 (dimanche) - 6 (samedi), null = tous les jours');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('type', ['delivery', 'pickup', 'both'])->default('both');
            $table->unsignedInteger('capacity')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_slot_templates');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();

            $table->enum('status', [
                'draft', 'quote_sent', 'confirmed', 'deposit_paid', 'ongoing', 'completed', 'cancelled',
            ])->default('draft');

            $table->date('event_start_date');
            $table->date('event_end_date');

            $table->enum('delivery_method', ['delivery', 'pickup']);
            $table->string('delivery_address')->nullable();
            $table->foreignId('delivery_slot_template_id')->nullable()->constrained('delivery_slot_templates')->nullOnDelete();
            $table->date('delivery_date')->nullable();
            $table->foreignId('return_slot_template_id')->nullable()->constrained('delivery_slot_templates')->nullOnDelete();
            $table->date('return_date')->nullable();

            $table->text('notes')->nullable();

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('deposit_required', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('currency', 3)->default('XOF');

            $table->string('quote_pdf_path')->nullable();
            $table->timestamp('quote_generated_at')->nullable();
            $table->timestamp('hold_expires_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            $table->index(['event_start_date', 'event_end_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};

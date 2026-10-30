<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

#[Fillable([
    'reference', 'customer_id', 'status', 'type',
    'event_start_date', 'event_end_date',
    'delivery_method', 'delivery_address',
    'delivery_slot_template_id', 'delivery_date',
    'return_slot_template_id', 'return_date',
    'notes', 'subtotal', 'deposit_required', 'discount', 'total', 'currency',
    'quote_pdf_path', 'quote_generated_at', 'hold_expires_at', 'confirmed_at', 'cancelled_at',
])]
class Reservation extends Model
{
    use HasFactory;

    /**
     * Statuses that keep stock blocked for the reservation's period.
     * "quote_sent" only blocks stock while its hold has not expired (see scopeBlockingStock).
     */
    public const BLOCKING_STATUSES = ['quote_sent', 'confirmed', 'deposit_paid', 'ongoing'];

    public const QUOTE_HOLD_HOURS = 48;

    protected function casts(): array
    {
        return [
            'event_start_date' => 'date',
            'event_end_date' => 'date',
            'delivery_date' => 'date',
            'return_date' => 'date',
            'subtotal' => 'decimal:2',
            'deposit_required' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'quote_generated_at' => 'datetime',
            'hold_expires_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Reservation $reservation) {
            $reservation->reference ??= static::generateReference();
        });
    }

    public static function generateReference(): string
    {
        $year = now()->year;
        $sequence = static::whereYear('created_at', $year)->count() + 1;

        return sprintf('RES-%d-%05d-%s', $year, $sequence, Str::upper(Str::random(3)));
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReservationItem::class);
    }

    public function deliverySlot(): BelongsTo
    {
        return $this->belongsTo(DeliverySlotTemplate::class, 'delivery_slot_template_id');
    }

    public function returnSlot(): BelongsTo
    {
        return $this->belongsTo(DeliverySlotTemplate::class, 'return_slot_template_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ItemReview::class);
    }

    /** Only finished orders can be reviewed, so every published review comes from a real customer. */
    public function canBeReviewed(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Private link the customer uses to review the items of this order. The
     * token is generated on first use and kept, so a link sent earlier keeps working.
     */
    public function reviewUrl(): string
    {
        if (! $this->review_token) {
            $this->forceFill(['review_token' => Str::random(40)])->save();
        }

        return rtrim(config('app.frontend_url'), '/')."/fr/avis/{$this->reference}?t={$this->review_token}";
    }

    /** wa.me link opening a chat with the customer, pre-filled with the review request. */
    public function reviewWhatsappUrl(): ?string
    {
        $phone = preg_replace('/\D+/', '', (string) $this->customer?->phone);
        if ($phone === '') {
            return null;
        }
        // Togolese numbers are often typed without the country code.
        if (strlen($phone) === 8) {
            $phone = '228'.$phone;
        }

        $message = implode("\n", [
            "Bonjour {$this->customer->first_name}, merci d'avoir fait confiance à La Perle d'Or pour votre événement !",
            'Votre avis nous aide beaucoup. Pouvez-vous noter le mobilier reçu ? Cela prend moins d\'une minute :',
            $this->reviewUrl(),
        ]);

        return "https://wa.me/{$phone}?text=".rawurlencode($message);
    }

    /**
     * Reservations whose items currently occupy stock: confirmed bookings,
     * plus quotes still within their hold window.
     */
    public function scopeBlockingStock(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereIn('status', ['confirmed', 'deposit_paid', 'ongoing'])
                ->orWhere(function (Builder $q2) {
                    $q2->where('status', 'quote_sent')
                        ->where(function (Builder $q3) {
                            $q3->whereNull('hold_expires_at')
                                ->orWhere('hold_expires_at', '>=', Carbon::now());
                        });
                });
        });
    }

    public function whatsappMessage(): string
    {
        $isRental = $this->type === 'rental';

        $lines = [
            $isRental
                ? "Bonjour, je souhaite confirmer ma demande de devis {$this->reference}."
                : "Bonjour, je souhaite confirmer ma commande {$this->reference}.",
        ];

        if ($isRental && $this->event_start_date && $this->event_end_date) {
            $lines[] = 'Période : '.$this->event_start_date->format('d/m/Y').' au '.$this->event_end_date->format('d/m/Y');
        }

        $lines[] = 'Total estimé : '.number_format((float) $this->total, 0, ',', ' ')." {$this->currency}";

        return implode("\n", $lines);
    }
}

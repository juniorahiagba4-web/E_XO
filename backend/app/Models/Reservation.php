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
    'event_start_date', 'event_end_date', 'purchase_date',
    'delivery_method', 'delivery_address',
    'delivery_slot_template_id', 'delivery_date', 'delivery_time',
    'return_slot_template_id', 'return_date', 'return_time',
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
    public const BLOCKING_STATUSES = ['quote_sent', 'confirmed'];

    public const QUOTE_HOLD_HOURS = 48;

    /** What the admin panel, exports and documents show for each stored code. */
    /**
     * Envoyé: the customer sent the request. Confirmé: paid in part or in
     * full. Terminé: furniture delivered (and collected back for a rental).
     */
    public const STATUS_LABELS = [
        'quote_sent' => 'Envoyé',
        'confirmed' => 'Confirmé',
        'completed' => 'Terminé',
        'cancelled' => 'Annulé',
    ];

    public const TYPE_LABELS = [
        'rental' => 'Location',
        'purchase' => 'Achat',
    ];

    public const DELIVERY_METHOD_LABELS = [
        'delivery' => 'Livraison à domicile',
        'pickup' => 'Retrait en boutique',
    ];

    protected function casts(): array
    {
        return [
            'event_start_date' => 'date',
            'event_end_date' => 'date',
            'purchase_date' => 'date',
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

        // Keep track of when an order was confirmed or cancelled, whoever
        // changed the status.
        static::saving(function (Reservation $reservation) {
            if (! $reservation->isDirty('status')) {
                return;
            }
            if ($reservation->status === 'confirmed') {
                $reservation->confirmed_at ??= now();
            }
            if ($reservation->status === 'cancelled') {
                $reservation->cancelled_at ??= now();
            }
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
            $q->whereIn('status', ['confirmed'])
                ->orWhere(function (Builder $q2) {
                    $q2->where('status', 'quote_sent')
                        ->where(function (Builder $q3) {
                            $q3->whereNull('hold_expires_at')
                                ->orWhere('hold_expires_at', '>=', Carbon::now());
                        });
                });
        });
    }

    /**
     * The delivery the customer asked for, e.g. "le 12/11/2026 à 14:30",
     * or null when they pick the furniture up themselves.
     */
    public function deliveryWish(): ?string
    {
        if ($this->delivery_method !== 'delivery') {
            return null;
        }

        return static::describeMoment($this->delivery_date, $this->delivery_time);
    }

    /** Number of billed days of a rental (at least one), null for a purchase. */
    public function rentalDays(): ?int
    {
        if ($this->type !== 'rental' || ! $this->event_start_date || ! $this->event_end_date) {
            return null;
        }

        return max(1, (int) round($this->event_start_date->diffInDays($this->event_end_date)));
    }

    /**
     * Recomputes every line and the totals from the stored unit prices, so a
     * price typed in the admin (e.g. for a "sur devis" item) or new rental
     * dates are reflected everywhere. The discount is kept as is.
     */
    public function recalculateTotals(): void
    {
        $days = $this->rentalDays();
        $subtotal = 0.0;

        foreach ($this->items()->get() as $line) {
            $lineSubtotal = $this->type === 'rental'
                ? (float) $line->unit_price_per_day * $line->quantity * ($days ?? 1)
                : (float) ($line->unit_sale_price ?? 0) * $line->quantity;

            if ((float) $line->subtotal !== $lineSubtotal) {
                $line->update(['subtotal' => $lineSubtotal]);
            }
            $subtotal += $lineSubtotal;
        }

        $this->update([
            'subtotal' => $subtotal,
            'total' => max(0, $subtotal - (float) $this->discount),
        ]);
    }

    /** When the furniture is collected back, e.g. "le 23/11/2026 à 14h30". */
    public function returnWish(): ?string
    {
        if ($this->type !== 'rental' || $this->delivery_method !== 'delivery') {
            return null;
        }

        return static::describeMoment($this->return_date ?? $this->event_end_date, $this->return_time);
    }

    public static function describeMoment(?Carbon $date, ?string $time): ?string
    {
        $parts = array_filter([
            $date ? 'le '.$date->format('d/m/Y') : null,
            $time ? 'à '.str_replace(':', 'h', substr($time, 0, 5)) : null,
        ]);

        return $parts ? implode(' ', $parts) : null;
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

        if ($wish = $this->deliveryWish()) {
            $lines[] = "Livraison souhaitée : {$wish}";
        }

        if ($return = $this->returnWish()) {
            $lines[] = "Retour du mobilier : {$return}";
        }

        $lines[] = 'Total estimé : '.number_format((float) $this->total, 0, ',', ' ').' FCFA';

        return implode("\n", $lines);
    }
}

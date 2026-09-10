<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use Illuminate\Console\Command;

class ExpireStaleQuotes extends Command
{
    protected $signature = 'reservations:expire-stale-quotes';

    protected $description = 'Cancel quotes whose 48h hold has expired without being confirmed, freeing their stock.';

    public function handle(): int
    {
        $count = Reservation::query()
            ->where('status', 'quote_sent')
            ->whereNotNull('hold_expires_at')
            ->where('hold_expires_at', '<', now())
            ->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        $this->info("Expired {$count} stale quote(s).");

        return self::SUCCESS;
    }
}

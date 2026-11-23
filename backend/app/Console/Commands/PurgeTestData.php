<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Item;
use App\Models\ItemReview;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Empties everything that was created while testing the site (orders,
 * quotes, customers, customer accounts, reviews, notifications...) so the
 * admin starts clean for real use. The catalogue (categories, items,
 * photos, promotions), the settings and the admin/manager accounts are kept.
 *
 * Without --force it only shows what would be deleted.
 */
class PurgeTestData extends Command
{
    protected $signature = 'app:purge-test-data
        {--force : Really delete (otherwise only shows the counts)}
        {--keep-user=* : E-mail of a customer account to keep}';

    protected $description = 'Delete test orders, quotes, customers and reviews before going live';

    public function handle(): int
    {
        $staffIds = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'manager']))->pluck('id');
        $customerUsers = User::whereNotIn('id', $staffIds)
            ->whereNotIn('email', (array) $this->option('keep-user'));

        $quoteFiles = collect(Storage::disk('public')->files('quotes'));

        $counts = [
            'Commandes et devis' => Reservation::count(),
            'Lignes de commande' => ReservationItem::count(),
            'Clients' => Customer::count(),
            'Comptes clients du site' => (clone $customerUsers)->count(),
            'Avis laissés par des clients' => ItemReview::whereNotNull('reservation_id')->orWhereNotNull('customer_id')->count(),
            'Favoris' => DB::table('favorites')->count(),
            'Notifications de l\'admin' => DB::table('notifications')->count(),
            'Messages du formulaire de contact' => DB::table('contact_messages')->count(),
            'Tâches en erreur' => DB::table('failed_jobs')->count(),
            'Devis PDF' => $quoteFiles->count(),
        ];

        $this->table(['Données de test', 'Nombre'], collect($counts)->map(fn ($n, $label) => [$label, $n])->values());
        $this->line('Conservés : catégories, articles et photos, promotions, coordonnées et réglages, comptes admin/gestionnaire ('
            .User::whereIn('id', $staffIds)->pluck('email')->implode(', ').').');

        if (! $this->option('force')) {
            $this->warn('Simulation seulement : rien n\'a été supprimé. Relancez avec --force pour supprimer.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($customerUsers) {
            ItemReview::whereNotNull('reservation_id')->orWhereNotNull('customer_id')->delete();
            ReservationItem::query()->delete();
            Reservation::query()->delete();
            Customer::query()->delete();
            DB::table('favorites')->delete();
            DB::table('notifications')->delete();
            DB::table('contact_messages')->delete();
            DB::table('failed_jobs')->delete();

            $ids = (clone $customerUsers)->pluck('id');
            DB::table('personal_access_tokens')->where('tokenable_type', User::class)->whereIn('tokenable_id', $ids)->delete();
            DB::table('model_has_roles')->where('model_type', User::class)->whereIn('model_id', $ids)->delete();
            User::whereIn('id', $ids)->delete();

            // Placeholder ratings typed in by hand during the tests: the site
            // computes the stars from real reviews only.
            Item::query()->update(['rating' => null, 'rating_count' => 0]);
        });

        Storage::disk('public')->deleteDirectory('quotes');

        // Order numbers start again at RES-<year>-00001 (they count this
        // year's orders); reset the ids too so the first real order is n° 1.
        if (DB::getDriverName() === 'mysql') {
            foreach (['reservations', 'reservation_items', 'customers', 'item_reviews', 'notifications'] as $table) {
                if (Schema::hasTable($table)) {
                    DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
                }
            }
        }

        $this->info('Données de test supprimées. Le panneau d\'administration est prêt pour l\'exploitation.');

        return self::SUCCESS;
    }
}

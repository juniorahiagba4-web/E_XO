<?php

/**
 * Import a catalogue exported by deploy/export-catalogue.php into the
 * PRODUCTION database (MySQL). Run it from the server, as the deploy user:
 *
 *   cd /var/www/laperledor/backend
 *   php ../deploy/import-catalogue.php /tmp/catalogue.json
 *
 * The tables are replaced wholesale (the production database is empty at this
 * point) and the row ids are preserved so foreign keys stay valid.
 * Users, roles, reservations and customers are NOT touched.
 */

$backend = getenv('BACKEND_DIR') ?: dirname(__DIR__).'/backend';

if (! file_exists($backend.'/vendor/autoload.php')) {
    fwrite(STDERR, "Composer dependencies not found in {$backend}\n");
    exit(1);
}

$file = $argv[1] ?? null;
if (! $file || ! file_exists($file)) {
    fwrite(STDERR, "Usage: php import-catalogue.php <catalogue.json>\n");
    exit(1);
}

require $backend.'/vendor/autoload.php';
$app = require $backend.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$payload = json_decode(file_get_contents($file), true);
if (! is_array($payload)) {
    fwrite(STDERR, "Could not decode the JSON file.\n");
    exit(1);
}

$db = Illuminate\Support\Facades\DB::connection();

$db->transaction(function () use ($db, $payload) {
    if ($db->getDriverName() === 'mysql') {
        $db->statement('SET FOREIGN_KEY_CHECKS=0');
    }

    // Rows that point at catalogue entries are dropped first.
    $db->table('reservation_items')->delete();
    $db->table('favorites')->delete();
    $db->table('item_reviews')->delete();
    $db->table('promotions')->delete();
    $db->table('items')->delete();
    $db->table('categories')->delete();
    $db->table('delivery_slot_templates')->delete();

    foreach ($payload as $table => $data) {
        if (! isset($data['columns'], $data['rows']) || ! $data['rows']) {
            continue;
        }
        $chunk = array_chunk($data['rows'], 50);
        foreach ($chunk as $rows) {
            $db->table($table)->insert($rows);
        }

        fwrite(STDOUT, sprintf("  %-26s %4d rows imported\n", $table, count($data['rows'])));
    }

    if ($db->getDriverName() === 'mysql') {
        $db->statement('SET FOREIGN_KEY_CHECKS=1');
    }
});

// The auto-increment counters are reset *after* the transaction: an ALTER
// TABLE statement implicitly commits it in MySQL.
foreach ($payload as $table => $data) {
    if (! isset($data['columns'], $data['rows']) || ! $data['rows']) {
        continue;
    }
    $max = 0;
    foreach ($data['rows'] as $row) {
        $max = max($max, (int) ($row['id'] ?? 0));
    }
    if ($db->getDriverName() === 'mysql') {
        $db->statement('ALTER TABLE `'.$table.'` AUTO_INCREMENT = '.($max + 1));
    }
}

echo "\nCatalogue imported successfully.\n";

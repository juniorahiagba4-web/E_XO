<?php

/**
 * Export the catalogue from the LOCAL development database (SQLite) to JSON,
 * so it can be imported into the production MySQL database.
 *
 *   php deploy/export-catalogue.php > catalogue.json
 *   php deploy/export-catalogue.php catalogue.json
 *
 * Passing a file name is safer than redirecting the output: on some Windows
 * PHP builds a startup warning ("Module \"openssl\" is already loaded") is
 * printed on stdout and would corrupt the JSON.
 *
 * Only the catalogue tables are exported (categories, items, reviews,
 * promotions, delivery slots). Users, reservations and customers stay on the
 * production server — the admin account there is created by the seeder.
 *
 * The row ids are preserved so that foreign keys (promotion -> item,
 * review -> item) keep pointing at the right rows after the import.
 */

$root = dirname(__DIR__);
$sqlite = $root.'/backend/database/database.sqlite';

if (! file_exists($sqlite)) {
    fwrite(STDERR, "Local database not found: {$sqlite}\n");
    exit(1);
}

$tables = [
    'categories',
    'items',
    'item_reviews',
    'promotions',
    'delivery_slot_templates',
];

$pdo = new PDO('sqlite:'.$sqlite, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
$pdo->exec('PRAGMA foreign_keys = ON');

$export = [];

foreach ($tables as $table) {
    $columns = $pdo->query("PRAGMA table_info({$table})")->fetchAll(PDO::FETCH_ASSOC);
    if (! $columns) {
        fwrite(STDERR, "Skipping unknown table: {$table}\n");
        continue;
    }

    $names = array_column($columns, 'name');
    $sql = 'SELECT '.implode(', ', array_map(fn ($n) => '"'.$n.'"', $names)).' FROM "'.$table.'"';
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    // Normalise: sqlite has no native boolean, everything comes back as text.
    foreach ($rows as &$row) {
        foreach ($row as $key => $value) {
            if ($value === null) {
                continue;
            }
            if (in_array($key, ['is_active', 'sale_price_on_request', 'rating_count', 'capacity', 'total_stock', 'min_rental_quantity', 'weekday'], true)) {
                $row[$key] = (int) $value;
            }
        }

        // MySQL uses stricter column types than sqlite: unsigned integers and
        // unsigned decimals reject negative values, so clamp them here.
        if (array_key_exists('rating_count', $row)) {
            $row['rating_count'] = max(0, (int) $row['rating_count']);
        }
        if (array_key_exists('total_stock', $row)) {
            $row['total_stock'] = max(0, (int) $row['total_stock']);
        }
        if (array_key_exists('min_rental_quantity', $row)) {
            $row['min_rental_quantity'] = max(1, (int) $row['min_rental_quantity']);
        }
        if (array_key_exists('rating', $row) && $row['rating'] !== null) {
            $row['rating'] = max(0, min(5, (float) $row['rating']));
        }
        foreach (['deposit_amount', 'sale_price', 'rental_price_per_day', 'discount_value'] as $money) {
            if (array_key_exists($money, $row) && $row[$money] !== null) {
                $row[$money] = max(0, (float) $row[$money]);
            }
        }
    }
    unset($row);

    $export[$table] = [
        'columns' => $names,
        'rows' => $rows,
    ];

    fwrite(STDERR, sprintf("  %-26s %4d rows\n", $table, count($rows)));
}

$json = json_encode($export, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

$target = $argv[1] ?? null;
if ($target) {
    if (file_put_contents($target, $json."\n") === false) {
        fwrite(STDERR, "Could not write {$target}\n");
        exit(1);
    }
    fwrite(STDERR, "Catalogue written to {$target}\n");
} else {
    echo $json, "\n";
}

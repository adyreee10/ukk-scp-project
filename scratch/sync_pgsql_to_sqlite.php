<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Starting Sync from PgSQL (Supabase) to SQLite...\n";

$sqlitePdo = new PDO('sqlite:' . database_path('database.sqlite'));
$sqlitePdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sqlitePdo->exec('PRAGMA foreign_keys = OFF;');

$tables = [
    'roles',
    'majors',
    'academic_years',
    'system_configurations',
    'lesson_hours',
    'rooms',
    'classes',
    'users',
    'counselor_classes',
    'schedules',
    'journals',
    'attendances',
    'leave_requests',
    'personal_access_tokens',
];

foreach ($tables as $table) {
    echo "Syncing $table... ";
    
    // Check if table exists in SQLite
    $tableExists = $sqlitePdo->query("SELECT count(*) FROM sqlite_master WHERE type='table' AND name='$table'")->fetchColumn();
    if (!$tableExists) {
        echo "Table does not exist in SQLite, skipping.\n";
        continue;
    }

    // Delete existing rows in SQLite
    $sqlitePdo->exec("DELETE FROM $table;");

    // Fetch all rows from PgSQL
    $rows = DB::connection('pgsql')->table($table)->get();
    if ($rows->isEmpty()) {
        echo "0 rows (empty).\n";
        continue;
    }

    $firstRow = (array)$rows->first();
    $columns = array_keys($firstRow);
    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    $colList = implode(', ', array_map(fn($c) => "\"$c\"", $columns));

    $insertSql = "INSERT INTO $table ($colList) VALUES ($placeholders)";
    $stmt = $sqlitePdo->prepare($insertSql);

    $count = 0;
    $sqlitePdo->beginTransaction();
    foreach ($rows as $row) {
        $values = array_values((array)$row);
        // Format boolean / JSON if needed
        foreach ($values as $k => $v) {
            if (is_bool($v)) {
                $values[$k] = $v ? 1 : 0;
            }
        }
        $stmt->execute($values);
        $count++;
    }
    $sqlitePdo->commit();

    echo "$count rows synced.\n";
}

$sqlitePdo->exec('PRAGMA foreign_keys = ON;');

echo "\nVerification in SQLite:\n";
$rooms = $sqlitePdo->query("SELECT count(*) FROM rooms")->fetchColumn();
$scheds = $sqlitePdo->query("SELECT count(*) FROM schedules")->fetchColumn();
$users = $sqlitePdo->query("SELECT count(*) FROM users")->fetchColumn();
echo "SQLite now has: Rooms: $rooms, Schedules: $scheds, Users: $users\n";

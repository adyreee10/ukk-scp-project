<?php
/**
 * Helper Runner untuk cPanel Shared Hosting (tanpa SSH Terminal)
 * Kunjungi: https://ukk-scp-rezky.web.id/artisan_runner.php?action=setup
 * HAPUS file ini dari File Manager setelah selesai digunakan demi keamanan!
 */

$secretKey = 'ukk2026';
$key = $_GET['key'] ?? '';
$action = $_GET['action'] ?? 'setup';

if ($key !== $secretKey && $action !== 'setup') {
    die("Akses ditolak. Gunakan URL: artisan_runner.php?key={$secretKey}&action=setup");
}

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "<pre style='background:#0f172a;color:#38bdf8;padding:20px;border-radius:10px;font-family:monospace;font-size:14px;'>";
echo "=== UKK LARAVEL HOSTING HELPER ===\n\n";

try {
    if ($action === 'reset_rezky') {
        echo "Mereset kata sandi Rezky Aditya (NIS 17821) ke 'password123'...\n";
        \App\Models\User::where('nis', '17821')->orWhere('id', 16)->update([
            'password' => \Illuminate\Support\Facades\Hash::make('password123')
        ]);
        echo "✔ BERHASIL! Sekarang akun Rezky Aditya (NIS: 17821) sudah bisa login dengan password: password123\n";
    }

    if ($action === 'setup' || $action === 'storage_link') {
        echo "[1/3] Menjalankan php artisan storage:link ...\n";
        $kernel->call('storage:link');
        echo $kernel->output() . "\n";
    }

    if ($action === 'setup' || $action === 'optimize') {
        echo "[2/3] Menjalankan php artisan optimize:clear ...\n";
        $kernel->call('optimize:clear');
        echo $kernel->output() . "\n";

        echo "[3/3] Menjalankan php artisan config:cache & route:cache ...\n";
        $kernel->call('config:cache');
        echo $kernel->output() . "\n";
        $kernel->call('route:cache');
        echo $kernel->output() . "\n";
        $kernel->call('view:cache');
        echo $kernel->output() . "\n";
    }

    echo "\n✔ SELURUH PROSES BERHASIL!\n";
    echo "PENTING: Jangan lupa hapus file 'public/artisan_runner.php' dari File Manager setelah ini demi keamanan website Anda.\n";
} catch (\Throwable $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";


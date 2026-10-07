<?php

use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;

it('allows Waka Kurikulum to manage classes and rooms', function () {
    actingAsRole('Waka Kurikulum');

    // Coba buat Kelas
    $this->postJson('/api/waka/classes', ['class_name' => 'XII RPL 1'])
         ->assertCreated();
    expect(SchoolClass::where('class_name', 'XII RPL 1')->exists())->toBeTrue();

    // Coba buat Ruangan
    $this->postJson('/api/waka/rooms', ['room_name' => 'Lab AI'])
         ->assertCreated();
    expect(Room::where('room_name', 'Lab AI')->exists())->toBeTrue();
});

it('prevents non-waka from accessing master data', function () {
    // Guru mencoba menyusup ke endpoint Waka
    actingAsRole('Guru');

    $this->postJson('/api/waka/classes', ['class_name' => 'Hacker Class'])
         ->assertForbidden(); // Harus ditolak Satpam
});

it('validates configuration updates', function () {
    $waka = actingAsRole('Waka Kurikulum');

    SystemConfiguration::create([
        'id' => 1,
        'active_cycle' => 'Putaran 1',
        'is_schedule_locked' => false,
        'updated_by' => $waka->id,
    ]);

    // Waka mengubah konfigurasi sistem
    $this->putJson('/api/waka/configuration', [
        'active_cycle' => 'Putaran 2',
        'is_schedule_locked' => true,
    ])->assertOk();

    // Pastikan database benar-benar berubah
    $config = SystemConfiguration::find(1);
    expect($config->active_cycle)->toBe('Putaran 2')
          ->and($config->is_schedule_locked)->toBeTrue();
});

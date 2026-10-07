<?php

use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

it('validates photo uploads correctly', function () {
    Carbon::setTestNow('2026-08-24 08:30:00'); 
    
    Storage::fake('public');

    $guru = userWithRole('Guru');
    $kelas = SchoolClass::create(['class_name' => 'X RPL 1']);
    $ruangan = Room::create(['room_name' => 'Lab Komputer']);
    
    SystemConfiguration::create([
        'id' => 1,
        'active_cycle' => 'Putaran 1',
        'is_schedule_locked' => false,
        'updated_by' => $guru->id,
    ]);

    $schedule = Schedule::create([
        'teacher_id' => $guru->id,
        'class_id' => $kelas->id,
        'room_id' => $ruangan->id,
        'subject' => 'Web',
        'day_of_week' => 'Senin', // Sesuai dengan tanggal beku
        'start_time' => '08:00',
        'end_time' => '10:00',
        'cycle_type' => 'Putaran 1',
    ]);

    \Laravel\Sanctum\Sanctum::actingAs($guru);
    
    $basePayload = [
        'schedule_id' => $schedule->id,
        'date' => '2026-08-24',
        'topic_description' => 'Materi Upload',
    ];

    $this->postJson('/api/teacher/journals', $basePayload)
         ->assertUnprocessable()
         ->assertJsonValidationErrors(['photo']);

    $pdf = UploadedFile::fake()->create('hacker_document.pdf', 100, 'application/pdf');
    $this->postJson('/api/teacher/journals', [...$basePayload, 'photo' => $pdf])
         ->assertUnprocessable()
         ->assertJsonValidationErrors(['photo']);

    $hugeImage = UploadedFile::fake()->image('gambar_raksasa.jpg')->size(3000); 
    $this->postJson('/api/teacher/journals', [...$basePayload, 'photo' => $hugeImage])
         ->assertUnprocessable()
         ->assertJsonValidationErrors(['photo']);

    $validImage = UploadedFile::fake()->image('foto_kelas_sah.jpg');
    $response = $this->postJson('/api/teacher/journals', [...$basePayload, 'photo' => $validImage]);
    
    $response->assertCreated();
    
    Storage::disk('public')->assertExists('journals/' . $validImage->hashName());

    Carbon::setTestNow();
});

it('validates leave request document uploads', function () {
    Storage::fake('public');

    $siswa = userWithRole('Siswa');
    \Laravel\Sanctum\Sanctum::actingAs($siswa);
    
    $basePayload = [
        'start_date' => now()->toDateString(),
        'end_date' => now()->toDateString(),
        'leave_type' => 'Sakit',
        'reason' => 'Sakit perut',
    ];

    // SKENARIO 5: File executable ditolak (422)
    $exeFile = UploadedFile::fake()->create('virus.exe', 100, 'application/x-msdownload');
    $this->postJson('/api/leave-requests', [...$basePayload, 'document' => $exeFile])
         ->assertUnprocessable()
         ->assertJsonValidationErrors(['document']);

    // SKENARIO 6: File PDF > 4MB ditolak (kita buat 5MB)
    $hugePdf = UploadedFile::fake()->create('besar.pdf', 5000, 'application/pdf');
    $this->postJson('/api/leave-requests', [...$basePayload, 'document' => $hugePdf])
         ->assertUnprocessable()
         ->assertJsonValidationErrors(['document']);

    // SKENARIO 7: Dokumen PDF valid tersimpan (201)
    $validPdf = UploadedFile::fake()->create('surat_dokter.pdf', 100, 'application/pdf');
    $response = $this->postJson('/api/leave-requests', [...$basePayload, 'document' => $validPdf]);
    
    $response->assertCreated();
    Storage::disk('public')->assertExists('leave_documents/' . $validPdf->hashName());
});


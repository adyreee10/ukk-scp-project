<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Journal;
use App\Models\Schedule;

class JournalSeeder extends Seeder
{
    public function run(): void
    {
        $schedule = Schedule::first();

        Journal::create([
            'schedule_id' => $schedule->id,
            'date' => '2026-08-06',
            'topic_description' => 'Pengenalan Pemrograman Web',
            'photo_path' => null,
        ]);
    }
}

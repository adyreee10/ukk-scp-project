<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\Schedule;


class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = User::where('username', 'guru')->first();
        $class = SchoolClass::first();
        $room = Room::first();

        Schedule::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'room_id' => $room->id,
            'subject' => 'Pemrograman Web',
            'day_of_week' => 'Senin',
            'start_time' => '07:00',
            'end_time' => '08:30',
            'cycle_type' => 'Putaran 1'
        ]);
    }
}

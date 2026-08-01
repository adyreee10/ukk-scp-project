<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        Room::insert([
            ['room_name' => 'Ruang 101'],
            ['room_name' => 'Ruang 102'],
            ['room_name' => 'Lab RPL'],
            ['room_name' => 'Lab TKJ'],
            ['room_name' => 'Lab Multimedia'],
        ]);
    }
}
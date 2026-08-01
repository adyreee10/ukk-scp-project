<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SchoolClass;

class SchoolClassSeeder extends Seeder
{
    public function run(): void
    {
        SchoolClass::insert([
            ['class_name' => 'X RPL 1'],
            ['class_name' => 'X RPL 2'],
            ['class_name' => 'XI RPL 1'],
            ['class_name' => 'XI RPL 2'],
            ['class_name' => 'XII RPL 1'],
            ['class_name' => 'XII RPL 2'],
        ]);
    }
}
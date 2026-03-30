<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $teams = [
            ['name' => 'Chelsea', 'strength' => 85],
            ['name' => 'Arsenal', 'strength' => 80],
            ['name' => 'Manchester City', 'strength' => 82],
            ['name' => 'Liverpool', 'strength' => 78],
        ];

        foreach ($teams as $team) {
            Team::firstOrCreate(['name' => $team['name']], $team);
        }
    }
}

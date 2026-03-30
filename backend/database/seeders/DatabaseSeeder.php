<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Services\FixtureService;
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

        // Generate fixtures after teams are seeded
        app(FixtureService::class)->generate();
    }
}

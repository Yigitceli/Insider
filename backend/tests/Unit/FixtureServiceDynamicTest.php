<?php

namespace Tests\Unit;

use App\Models\FootballMatch;
use App\Models\Team;
use App\Services\FixtureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FixtureServiceDynamicTest extends TestCase
{
    use RefreshDatabase;

    private FixtureService $fixtureService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixtureService = app(FixtureService::class);
    }

    public function test_two_teams(): void
    {
        Team::create(['name' => 'Team A', 'strength' => 90]);
        Team::create(['name' => 'Team B', 'strength' => 80]);

        $this->fixtureService->generate();

        // 2 teams: 1 match per leg × 2 legs = 2 matches, 2 weeks
        $this->assertEquals(2, FootballMatch::count());
        $this->assertEquals(2, $this->fixtureService->getTotalWeeks());
        $this->assertEquals(0, FootballMatch::whereColumn('home_team_id', 'away_team_id')->count());
    }

    public function test_three_teams(): void
    {
        Team::create(['name' => 'Team A', 'strength' => 90]);
        Team::create(['name' => 'Team B', 'strength' => 80]);
        Team::create(['name' => 'Team C', 'strength' => 70]);

        $this->fixtureService->generate();

        // 3 teams (odd): effective 4 → 3 weeks per leg × 2 = 6 weeks
        // But each week has 1 bye, so 1 match per week × 6 weeks = 6 matches
        $totalWeeks = $this->fixtureService->getTotalWeeks();
        $this->assertEquals(6, $totalWeeks);
        $this->assertEquals(6, FootballMatch::count());

        // Each team plays 4 matches (2 against each opponent)
        foreach (Team::all() as $team) {
            $count = FootballMatch::where('home_team_id', $team->id)
                ->orWhere('away_team_id', $team->id)
                ->count();
            $this->assertEquals(4, $count, "{$team->name} should play 4 matches");
        }

        $this->assertEquals(0, FootballMatch::whereColumn('home_team_id', 'away_team_id')->count());
    }

    public function test_five_teams(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            Team::create(['name' => "Team $i", 'strength' => 60 + $i * 5]);
        }

        $this->fixtureService->generate();

        // 5 teams (odd): effective 6 → 5 weeks per leg × 2 = 10 weeks
        // 20 matches total (5 teams, each plays 4 others × 2 = 8 matches, 5×8/2 = 20)
        $totalWeeks = $this->fixtureService->getTotalWeeks();
        $this->assertEquals(10, $totalWeeks);
        $this->assertEquals(20, FootballMatch::count());

        // Each team plays 8 matches
        foreach (Team::all() as $team) {
            $count = FootballMatch::where('home_team_id', $team->id)
                ->orWhere('away_team_id', $team->id)
                ->count();
            $this->assertEquals(8, $count, "{$team->name} should play 8 matches");
        }

        $this->assertEquals(0, FootballMatch::whereColumn('home_team_id', 'away_team_id')->count());
    }

    public function test_six_teams(): void
    {
        for ($i = 1; $i <= 6; $i++) {
            Team::create(['name' => "Team $i", 'strength' => 60 + $i * 5]);
        }

        $this->fixtureService->generate();

        // 6 teams: 5 weeks per leg × 2 = 10 weeks, 3 matches per week = 30 matches
        $totalWeeks = $this->fixtureService->getTotalWeeks();
        $this->assertEquals(10, $totalWeeks);
        $this->assertEquals(30, FootballMatch::count());

        // Each team plays 10 matches
        foreach (Team::all() as $team) {
            $count = FootballMatch::where('home_team_id', $team->id)
                ->orWhere('away_team_id', $team->id)
                ->count();
            $this->assertEquals(10, $count, "{$team->name} should play 10 matches");
        }

        $this->assertEquals(0, FootballMatch::whereColumn('home_team_id', 'away_team_id')->count());
    }

    public function test_one_team_throws_exception(): void
    {
        Team::create(['name' => 'Lonely Team', 'strength' => 90]);

        $this->expectException(\InvalidArgumentException::class);
        $this->fixtureService->generate();
    }

    public function test_double_leg_any_team_count(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            Team::create(['name' => "Team $i", 'strength' => 60 + $i * 5]);
        }

        $this->fixtureService->generate();

        $teams = Team::all();

        // Each pair plays exactly twice: once home, once away
        for ($i = 0; $i < $teams->count(); $i++) {
            for ($j = $i + 1; $j < $teams->count(); $j++) {
                $a = $teams[$i];
                $b = $teams[$j];

                $aHome = FootballMatch::where('home_team_id', $a->id)
                    ->where('away_team_id', $b->id)->count();
                $bHome = FootballMatch::where('home_team_id', $b->id)
                    ->where('away_team_id', $a->id)->count();

                $this->assertEquals(1, $aHome, "{$a->name} should host {$b->name} once");
                $this->assertEquals(1, $bHome, "{$b->name} should host {$a->name} once");
            }
        }
    }
}

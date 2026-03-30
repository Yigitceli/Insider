<?php

namespace Tests\Unit;

use App\Models\FootballMatch;
use App\Models\Team;
use App\Services\FixtureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FixtureServiceTest extends TestCase
{
    use RefreshDatabase;

    private FixtureService $fixtureService;

    protected function setUp(): void
    {
        parent::setUp();

        Team::create(['name' => 'Team A', 'strength' => 90]);
        Team::create(['name' => 'Team B', 'strength' => 80]);
        Team::create(['name' => 'Team C', 'strength' => 70]);
        Team::create(['name' => 'Team D', 'strength' => 60]);

        $this->fixtureService = app(FixtureService::class);
    }

    public function test_generates_twelve_matches(): void
    {
        $this->fixtureService->generate();

        $this->assertEquals(12, FootballMatch::count());
    }

    public function test_generates_six_weeks(): void
    {
        $this->fixtureService->generate();

        $weeks = FootballMatch::distinct('week')->pluck('week')->sort()->values();

        $this->assertEquals([1, 2, 3, 4, 5, 6], $weeks->toArray());
    }

    public function test_two_matches_per_week(): void
    {
        $this->fixtureService->generate();

        for ($week = 1; $week <= 6; $week++) {
            $count = FootballMatch::where('week', $week)->count();
            $this->assertEquals(2, $count, "Week $week should have 2 matches");
        }
    }

    public function test_each_team_plays_six_matches(): void
    {
        $this->fixtureService->generate();

        $teams = Team::all();

        foreach ($teams as $team) {
            $matchCount = FootballMatch::where('home_team_id', $team->id)
                ->orWhere('away_team_id', $team->id)
                ->count();

            $this->assertEquals(6, $matchCount, "{$team->name} should play 6 matches");
        }
    }

    public function test_double_leg_format(): void
    {
        $this->fixtureService->generate();

        $teams = Team::all();

        // Each pair should play twice: once home, once away
        for ($i = 0; $i < $teams->count(); $i++) {
            for ($j = $i + 1; $j < $teams->count(); $j++) {
                $teamA = $teams[$i];
                $teamB = $teams[$j];

                $aHome = FootballMatch::where('home_team_id', $teamA->id)
                    ->where('away_team_id', $teamB->id)
                    ->count();

                $bHome = FootballMatch::where('home_team_id', $teamB->id)
                    ->where('away_team_id', $teamA->id)
                    ->count();

                $this->assertEquals(1, $aHome, "{$teamA->name} should host {$teamB->name} once");
                $this->assertEquals(1, $bHome, "{$teamB->name} should host {$teamA->name} once");
            }
        }
    }

    public function test_all_matches_start_unplayed(): void
    {
        $this->fixtureService->generate();

        $this->assertEquals(0, FootballMatch::where('is_played', true)->count());
        $this->assertEquals(12, FootballMatch::whereNull('home_goals')->count());
        $this->assertEquals(12, FootballMatch::whereNull('away_goals')->count());
    }

    public function test_reset_clears_old_matches(): void
    {
        $this->fixtureService->generate();
        $this->assertEquals(12, FootballMatch::count());

        $this->fixtureService->generate();
        $this->assertEquals(12, FootballMatch::count());
    }

    public function test_no_team_plays_itself(): void
    {
        $this->fixtureService->generate();

        $selfMatches = FootballMatch::whereColumn('home_team_id', 'away_team_id')->count();

        $this->assertEquals(0, $selfMatches);
    }
}

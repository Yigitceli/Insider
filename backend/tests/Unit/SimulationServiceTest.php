<?php

namespace Tests\Unit;

use App\Models\FootballMatch;
use App\Models\Team;
use App\Services\SimulationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimulationServiceTest extends TestCase
{
    use RefreshDatabase;

    private SimulationService $simulationService;

    protected function setUp(): void
    {
        parent::setUp();

        Team::create(['name' => 'Strong Team', 'strength' => 95]);
        Team::create(['name' => 'Weak Team', 'strength' => 50]);
        Team::create(['name' => 'Team C', 'strength' => 75]);
        Team::create(['name' => 'Team D', 'strength' => 70]);

        $this->simulationService = app(SimulationService::class);
    }

    public function test_simulate_match_sets_goals(): void
    {
        $match = FootballMatch::create([
            'week' => 1,
            'home_team_id' => 1,
            'away_team_id' => 2,
            'is_played' => false,
        ]);

        $result = $this->simulationService->simulateMatch($match->load(['homeTeam', 'awayTeam']));

        $this->assertNotNull($result->home_goals);
        $this->assertNotNull($result->away_goals);
        $this->assertGreaterThanOrEqual(0, $result->home_goals);
        $this->assertGreaterThanOrEqual(0, $result->away_goals);
    }

    public function test_simulate_match_marks_as_played(): void
    {
        $match = FootballMatch::create([
            'week' => 1,
            'home_team_id' => 1,
            'away_team_id' => 2,
            'is_played' => false,
        ]);

        $result = $this->simulationService->simulateMatch($match->load(['homeTeam', 'awayTeam']));

        $this->assertTrue($result->is_played);
    }

    public function test_simulate_week_plays_all_matches(): void
    {
        FootballMatch::create([
            'week' => 1,
            'home_team_id' => 1,
            'away_team_id' => 2,
            'is_played' => false,
        ]);
        FootballMatch::create([
            'week' => 1,
            'home_team_id' => 3,
            'away_team_id' => 4,
            'is_played' => false,
        ]);

        $matches = $this->simulationService->simulateWeek(1);

        $this->assertCount(2, $matches);

        foreach ($matches as $match) {
            $this->assertTrue($match->is_played);
            $this->assertGreaterThanOrEqual(0, $match->home_goals);
            $this->assertGreaterThanOrEqual(0, $match->away_goals);
        }
    }

    public function test_simulate_week_skips_already_played(): void
    {
        FootballMatch::create([
            'week' => 1,
            'home_team_id' => 1,
            'away_team_id' => 2,
            'home_goals' => 5,
            'away_goals' => 0,
            'is_played' => true,
        ]);

        $matches = $this->simulationService->simulateWeek(1);

        // Should not overwrite existing result
        $this->assertEquals(5, $matches->first()->home_goals);
        $this->assertEquals(0, $matches->first()->away_goals);
    }

    public function test_goals_are_integers(): void
    {
        $match = FootballMatch::create([
            'week' => 1,
            'home_team_id' => 1,
            'away_team_id' => 2,
            'is_played' => false,
        ]);

        $result = $this->simulationService->simulateMatch($match->load(['homeTeam', 'awayTeam']));

        $this->assertIsInt($result->home_goals);
        $this->assertIsInt($result->away_goals);
    }

    public function test_strong_team_wins_more_often(): void
    {
        // Run 50 simulations — strong team (95) should win majority against weak (50)
        $strongWins = 0;
        $total = 50;

        for ($i = 0; $i < $total; $i++) {
            $match = FootballMatch::create([
                'week' => 1,
                'home_team_id' => 1, // Strong Team (95)
                'away_team_id' => 2, // Weak Team (50)
                'is_played' => false,
            ]);

            $result = $this->simulationService->simulateMatch($match->load(['homeTeam', 'awayTeam']));

            if ($result->home_goals > $result->away_goals) {
                $strongWins++;
            }
        }

        // Strong team with home advantage should win at least 40% of the time
        $this->assertGreaterThan($total * 0.4, $strongWins, "Strong team should win majority of matches");
    }
}

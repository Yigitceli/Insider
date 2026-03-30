<?php

namespace Tests\Feature;

use App\Models\FootballMatch;
use App\Models\Team;
use App\Services\FixtureService;
use App\Services\SimulationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeagueApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Team::create(['name' => 'Chelsea', 'strength' => 85]);
        Team::create(['name' => 'Arsenal', 'strength' => 80]);
        Team::create(['name' => 'Manchester City', 'strength' => 82]);
        Team::create(['name' => 'Liverpool', 'strength' => 78]);

        app(FixtureService::class)->generate();
    }

    public function test_health_endpoint(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'database', 'timestamp'])
            ->assertJson(['status' => 'ok', 'database' => 'connected']);
    }

    public function test_league_index_returns_initial_state(): void
    {
        $response = $this->getJson('/api/league');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'league_table',
                'current_week',
                'last_played_week',
                'matches',
                'predictions',
                'is_finished',
            ])
            ->assertJson([
                'current_week' => 1,
                'last_played_week' => null,
                'predictions' => null,
                'is_finished' => false,
            ]);

        $this->assertCount(4, $response->json('league_table'));
    }

    public function test_next_week_plays_matches(): void
    {
        $response = $this->postJson('/api/league/next-week');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'week',
                'matches',
                'league_table',
                'predictions',
                'is_finished',
            ])
            ->assertJson(['week' => 1, 'is_finished' => false]);

        $this->assertCount(2, $response->json('matches'));

        // Verify matches are played in DB
        $this->assertEquals(2, FootballMatch::where('is_played', true)->count());
    }

    public function test_next_week_returns_error_when_league_finished(): void
    {
        // Play all 6 weeks
        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/league/next-week');
        }

        $response = $this->postJson('/api/league/next-week');

        $response->assertStatus(400)
            ->assertJson(['message' => 'League is already finished.']);
    }

    public function test_play_all_simulates_all_remaining_weeks(): void
    {
        $response = $this->postJson('/api/league/play-all');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'results',
                'league_table',
                'predictions',
                'is_finished',
            ])
            ->assertJson(['is_finished' => true]);

        $this->assertCount(6, $response->json('results'));
        $this->assertEquals(12, FootballMatch::where('is_played', true)->count());
    }

    public function test_play_all_from_midway(): void
    {
        // Play 3 weeks manually
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/league/next-week');
        }

        $response = $this->postJson('/api/league/play-all');

        $response->assertStatus(200)
            ->assertJson(['is_finished' => true]);

        // Should only contain remaining 3 weeks
        $this->assertCount(3, $response->json('results'));
        $this->assertEquals(12, FootballMatch::where('is_played', true)->count());
    }

    public function test_play_all_returns_error_when_league_finished(): void
    {
        $this->postJson('/api/league/play-all');

        $response = $this->postJson('/api/league/play-all');

        $response->assertStatus(400)
            ->assertJson(['message' => 'League is already finished.']);
    }

    public function test_reset_generates_new_fixtures(): void
    {
        // Play some matches
        $this->postJson('/api/league/next-week');

        $response = $this->postJson('/api/league/reset');

        $response->assertStatus(200)
            ->assertJson(['message' => 'League has been reset.']);

        $this->assertCount(12, $response->json('matches'));
        $this->assertEquals(0, FootballMatch::where('is_played', true)->count());
    }

    public function test_update_match_changes_score(): void
    {
        // Play week 1
        $this->postJson('/api/league/next-week');

        $match = FootballMatch::first();

        $response = $this->putJson("/api/matches/{$match->id}", [
            'home_goals' => 5,
            'away_goals' => 0,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['match', 'league_table', 'predictions']);

        $this->assertEquals(5, $response->json('match.home_goals'));
        $this->assertEquals(0, $response->json('match.away_goals'));
    }

    public function test_update_match_recalculates_standings(): void
    {
        $this->postJson('/api/league/next-week');

        $match = FootballMatch::first();
        $homeTeamId = $match->home_team_id;

        // Set a decisive win
        $response = $this->putJson("/api/matches/{$match->id}", [
            'home_goals' => 10,
            'away_goals' => 0,
        ]);

        $standings = $response->json('league_table');
        $homeStanding = collect($standings)->firstWhere('team_id', $homeTeamId);

        $this->assertEquals(3, $homeStanding['PTS']);
        $this->assertEquals(10, $homeStanding['GD']);
    }

    public function test_update_match_validates_input(): void
    {
        $this->postJson('/api/league/next-week');
        $match = FootballMatch::first();

        // Missing fields
        $this->putJson("/api/matches/{$match->id}", [])
            ->assertStatus(422);

        // Negative goals
        $this->putJson("/api/matches/{$match->id}", [
            'home_goals' => -1,
            'away_goals' => 0,
        ])->assertStatus(422);

        // Non-integer
        $this->putJson("/api/matches/{$match->id}", [
            'home_goals' => 'abc',
            'away_goals' => 0,
        ])->assertStatus(422);
    }

    public function test_update_nonexistent_match_returns_404(): void
    {
        $response = $this->putJson('/api/matches/999', [
            'home_goals' => 1,
            'away_goals' => 0,
        ]);

        $response->assertStatus(404);
    }

    public function test_predictions_appear_after_week_three(): void
    {
        // Weeks 1-2: no predictions (after playing, currentWeek becomes 2, 3)
        for ($i = 0; $i < 2; $i++) {
            $response = $this->postJson('/api/league/next-week');
            $this->assertNull($response->json('predictions'));
        }

        // Week 3: after playing, currentWeek becomes 4 → predictions appear
        $response = $this->postJson('/api/league/next-week');
        $this->assertNotNull($response->json('predictions'));
        $this->assertCount(4, $response->json('predictions'));
    }

    public function test_league_table_has_correct_structure(): void
    {
        $this->postJson('/api/league/next-week');

        $response = $this->getJson('/api/league');
        $standing = $response->json('league_table.0');

        $this->assertArrayHasKey('team_id', $standing);
        $this->assertArrayHasKey('team_name', $standing);
        $this->assertArrayHasKey('PTS', $standing);
        $this->assertArrayHasKey('P', $standing);
        $this->assertArrayHasKey('W', $standing);
        $this->assertArrayHasKey('D', $standing);
        $this->assertArrayHasKey('L', $standing);
        $this->assertArrayHasKey('GF', $standing);
        $this->assertArrayHasKey('GA', $standing);
        $this->assertArrayHasKey('GD', $standing);
    }

    public function test_full_league_flow(): void
    {
        // Play all 6 weeks one by one
        for ($week = 1; $week <= 6; $week++) {
            $response = $this->postJson('/api/league/next-week');
            $response->assertStatus(200);
            $this->assertEquals($week, $response->json('week'));
        }

        // Verify final state
        $response = $this->getJson('/api/league');
        $response->assertJson(['is_finished' => true]);

        // All 12 matches played
        $this->assertEquals(12, FootballMatch::where('is_played', true)->count());

        // Predictions show champion at 100%
        $predictions = $response->json('predictions');
        $this->assertEquals(100, $predictions[0]['percentage']);

        // Total points make sense: each week has 2 matches
        // Max possible points per match = 3, min = 1 (draw gives 2 total)
        $standings = $response->json('league_table');
        $totalPoints = array_sum(array_column($standings, 'PTS'));
        $this->assertGreaterThanOrEqual(12, $totalPoints); // At least all draws
        $this->assertLessThanOrEqual(36, $totalPoints);    // At most all decisive
    }
}

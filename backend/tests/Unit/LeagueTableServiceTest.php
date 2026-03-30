<?php

namespace Tests\Unit;

use App\Models\FootballMatch;
use App\Models\Team;
use App\Services\LeagueTableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeagueTableServiceTest extends TestCase
{
    use RefreshDatabase;

    private LeagueTableService $leagueTableService;
    private array $teams;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teams = [
            'A' => Team::create(['name' => 'Team A', 'strength' => 90]),
            'B' => Team::create(['name' => 'Team B', 'strength' => 80]),
            'C' => Team::create(['name' => 'Team C', 'strength' => 70]),
            'D' => Team::create(['name' => 'Team D', 'strength' => 60]),
        ];

        $this->leagueTableService = app(LeagueTableService::class);
    }

    public function test_empty_table_when_no_matches_played(): void
    {
        $standings = $this->leagueTableService->calculate();

        $this->assertCount(4, $standings);

        foreach ($standings as $standing) {
            $this->assertEquals(0, $standing['PTS']);
            $this->assertEquals(0, $standing['P']);
            $this->assertEquals(0, $standing['W']);
            $this->assertEquals(0, $standing['D']);
            $this->assertEquals(0, $standing['L']);
            $this->assertEquals(0, $standing['GD']);
        }
    }

    public function test_win_gives_three_points(): void
    {
        $this->createMatch($this->teams['A'], $this->teams['B'], 2, 0);

        $standings = $this->leagueTableService->calculate();
        $teamA = $this->findStanding($standings, $this->teams['A']->id);
        $teamB = $this->findStanding($standings, $this->teams['B']->id);

        $this->assertEquals(3, $teamA['PTS']);
        $this->assertEquals(1, $teamA['W']);
        $this->assertEquals(0, $teamA['L']);

        $this->assertEquals(0, $teamB['PTS']);
        $this->assertEquals(0, $teamB['W']);
        $this->assertEquals(1, $teamB['L']);
    }

    public function test_draw_gives_one_point_each(): void
    {
        $this->createMatch($this->teams['A'], $this->teams['B'], 1, 1);

        $standings = $this->leagueTableService->calculate();
        $teamA = $this->findStanding($standings, $this->teams['A']->id);
        $teamB = $this->findStanding($standings, $this->teams['B']->id);

        $this->assertEquals(1, $teamA['PTS']);
        $this->assertEquals(1, $teamA['D']);
        $this->assertEquals(1, $teamB['PTS']);
        $this->assertEquals(1, $teamB['D']);
    }

    public function test_goal_difference_calculated_correctly(): void
    {
        $this->createMatch($this->teams['A'], $this->teams['B'], 3, 1);

        $standings = $this->leagueTableService->calculate();
        $teamA = $this->findStanding($standings, $this->teams['A']->id);
        $teamB = $this->findStanding($standings, $this->teams['B']->id);

        $this->assertEquals(3, $teamA['GF']);
        $this->assertEquals(1, $teamA['GA']);
        $this->assertEquals(2, $teamA['GD']);

        $this->assertEquals(1, $teamB['GF']);
        $this->assertEquals(3, $teamB['GA']);
        $this->assertEquals(-2, $teamB['GD']);
    }

    public function test_sorting_by_points_then_gd_then_gf(): void
    {
        // Team A: 3 pts, GD +2, GF 3
        $this->createMatch($this->teams['A'], $this->teams['B'], 3, 1);
        // Team C: 3 pts, GD +2, GF 4
        $this->createMatch($this->teams['C'], $this->teams['D'], 4, 2);

        $standings = $this->leagueTableService->calculate();

        // Both have 3 pts and GD +2, but C has more GF (4 > 3)
        $this->assertEquals($this->teams['C']->id, $standings[0]['team_id']);
        $this->assertEquals($this->teams['A']->id, $standings[1]['team_id']);
    }

    public function test_sorting_points_over_gd(): void
    {
        // Team A: 3 pts, GD +1
        $this->createMatch($this->teams['A'], $this->teams['B'], 1, 0);
        // Team C: 1 pt, GD +0 (but big GD from another angle won't matter)
        $this->createMatch($this->teams['C'], $this->teams['D'], 5, 5);

        $standings = $this->leagueTableService->calculate();

        // A has more points (3 > 1), should be first
        $this->assertEquals($this->teams['A']->id, $standings[0]['team_id']);
    }

    public function test_unplayed_matches_are_ignored(): void
    {
        FootballMatch::create([
            'week' => 1,
            'home_team_id' => $this->teams['A']->id,
            'away_team_id' => $this->teams['B']->id,
            'home_goals' => null,
            'away_goals' => null,
            'is_played' => false,
        ]);

        $standings = $this->leagueTableService->calculate();

        foreach ($standings as $standing) {
            $this->assertEquals(0, $standing['P']);
        }
    }

    public function test_multiple_matches_accumulate(): void
    {
        // Team A wins twice
        $this->createMatch($this->teams['A'], $this->teams['B'], 2, 0);
        $this->createMatch($this->teams['A'], $this->teams['C'], 1, 0);

        $standings = $this->leagueTableService->calculate();
        $teamA = $this->findStanding($standings, $this->teams['A']->id);

        $this->assertEquals(6, $teamA['PTS']);
        $this->assertEquals(2, $teamA['P']);
        $this->assertEquals(2, $teamA['W']);
        $this->assertEquals(3, $teamA['GF']);
        $this->assertEquals(0, $teamA['GA']);
    }

    private function createMatch(Team $home, Team $away, int $homeGoals, int $awayGoals): void
    {
        FootballMatch::create([
            'week' => 1,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'home_goals' => $homeGoals,
            'away_goals' => $awayGoals,
            'is_played' => true,
        ]);
    }

    private function findStanding(array $standings, int $teamId): array
    {
        foreach ($standings as $standing) {
            if ($standing['team_id'] === $teamId) {
                return $standing;
            }
        }

        $this->fail("Standing not found for team $teamId");
    }
}

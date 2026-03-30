<?php

namespace Tests\Unit;

use App\Models\FootballMatch;
use App\Models\Team;
use App\Services\FixtureService;
use App\Services\PredictionService;
use App\Services\SimulationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PredictionServiceTest extends TestCase
{
    use RefreshDatabase;

    private PredictionService $predictionService;
    private SimulationService $simulationService;
    private FixtureService $fixtureService;

    protected function setUp(): void
    {
        parent::setUp();

        Team::create(['name' => 'Team A', 'strength' => 90]);
        Team::create(['name' => 'Team B', 'strength' => 80]);
        Team::create(['name' => 'Team C', 'strength' => 70]);
        Team::create(['name' => 'Team D', 'strength' => 60]);

        $this->predictionService = app(PredictionService::class);
        $this->simulationService = app(SimulationService::class);
        $this->fixtureService = app(FixtureService::class);
    }

    public function test_returns_null_before_week_four(): void
    {
        $this->fixtureService->generate();

        // No matches played → current week = 1
        $this->assertNull($this->predictionService->predict());

        // Play weeks 1-3
        $this->simulationService->simulateWeek(1);
        $this->assertNull($this->predictionService->predict());

        $this->simulationService->simulateWeek(2);
        $this->assertNull($this->predictionService->predict());

        $this->simulationService->simulateWeek(3);
        // After week 3, current week = 4 → predictions should appear
        $this->assertNotNull($this->predictionService->predict());
    }

    public function test_predictions_sum_to_hundred(): void
    {
        $this->fixtureService->generate();

        // Play 4 weeks to trigger predictions
        for ($week = 1; $week <= 4; $week++) {
            $this->simulationService->simulateWeek($week);
        }

        $predictions = $this->predictionService->predict();

        $this->assertNotNull($predictions);

        $total = array_sum(array_column($predictions, 'percentage'));
        $this->assertEquals(100, $total);
    }

    public function test_predictions_have_correct_structure(): void
    {
        $this->fixtureService->generate();

        for ($week = 1; $week <= 4; $week++) {
            $this->simulationService->simulateWeek($week);
        }

        $predictions = $this->predictionService->predict();

        $this->assertCount(4, $predictions);

        foreach ($predictions as $prediction) {
            $this->assertArrayHasKey('team_id', $prediction);
            $this->assertArrayHasKey('team_name', $prediction);
            $this->assertArrayHasKey('percentage', $prediction);
            $this->assertGreaterThanOrEqual(0, $prediction['percentage']);
            $this->assertLessThanOrEqual(100, $prediction['percentage']);
        }
    }

    public function test_champion_gets_hundred_percent_when_league_finished(): void
    {
        $this->fixtureService->generate();

        for ($week = 1; $week <= 6; $week++) {
            $this->simulationService->simulateWeek($week);
        }

        $predictions = $this->predictionService->predict();

        $this->assertNotNull($predictions);
        $this->assertEquals(100, $predictions[0]['percentage']);

        // All others should be 0
        for ($i = 1; $i < count($predictions); $i++) {
            $this->assertEquals(0, $predictions[$i]['percentage']);
        }
    }

    public function test_predictions_sorted_by_percentage_descending(): void
    {
        $this->fixtureService->generate();

        for ($week = 1; $week <= 4; $week++) {
            $this->simulationService->simulateWeek($week);
        }

        $predictions = $this->predictionService->predict();

        for ($i = 0; $i < count($predictions) - 1; $i++) {
            $this->assertGreaterThanOrEqual(
                $predictions[$i + 1]['percentage'],
                $predictions[$i]['percentage']
            );
        }
    }

    public function test_all_percentages_are_integers(): void
    {
        $this->fixtureService->generate();

        for ($week = 1; $week <= 4; $week++) {
            $this->simulationService->simulateWeek($week);
        }

        $predictions = $this->predictionService->predict();

        foreach ($predictions as $prediction) {
            $this->assertIsInt($prediction['percentage']);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\FixtureService;
use App\Services\LeagueTableService;
use App\Services\PredictionService;
use App\Services\SimulationService;
use App\Http\Requests\UpdateMatchRequest;
use Illuminate\Http\JsonResponse;

class LeagueController extends Controller
{
    public function __construct(
        private FixtureService $fixtureService,
        private SimulationService $simulationService,
        private LeagueTableService $leagueTableService,
        private PredictionService $predictionService,
    ) {}

    private function totalWeeks(): int
    {
        return $this->fixtureService->getTotalWeeks();
    }

    /**
     * GET /api/teams
     * Returns all teams.
     */
    public function teams(): JsonResponse
    {
        return response()->json([
            'teams' => $this->fixtureService->getTeams(),
        ]);
    }

    /**
     * GET /api/fixtures
     * Returns all matches (fixture view).
     */
    public function fixtures(): JsonResponse
    {
        return response()->json([
            'matches' => $this->fixtureService->getAllMatches(),
        ]);
    }

    /**
     * GET /api/league
     * Returns league table, current week matches, and predictions.
     */
    public function index(): JsonResponse
    {
        $currentWeek = $this->simulationService->getCurrentWeek();
        $lastPlayedWeek = $currentWeek - 1;
        $totalWeeks = $this->totalWeeks();

        return response()->json([
            'league_table' => $this->leagueTableService->calculate(),
            'current_week' => $currentWeek,
            'total_weeks' => $totalWeeks,
            'last_played_week' => $lastPlayedWeek > 0 ? $lastPlayedWeek : null,
            'matches' => $lastPlayedWeek > 0
                ? $this->simulationService->getMatchesByWeek($lastPlayedWeek)
                : [],
            'predictions' => $this->predictionService->predict(),
            'is_finished' => $currentWeek > $totalWeeks,
        ]);
    }

    /**
     * POST /api/league/next-week
     * Simulate the next week's matches.
     */
    public function nextWeek(): JsonResponse
    {
        $currentWeek = $this->simulationService->getCurrentWeek();
        $totalWeeks = $this->totalWeeks();

        if ($currentWeek > $totalWeeks) {
            return response()->json([
                'message' => 'League is already finished.',
            ], 400);
        }

        $matches = $this->simulationService->simulateWeek($currentWeek);

        return response()->json([
            'week' => $currentWeek,
            'matches' => $matches,
            'league_table' => $this->leagueTableService->calculate(),
            'predictions' => $this->predictionService->predict(),
            'is_finished' => $currentWeek >= $totalWeeks,
        ]);
    }

    /**
     * POST /api/league/play-all
     * Simulate all remaining weeks.
     */
    public function playAll(): JsonResponse
    {
        $currentWeek = $this->simulationService->getCurrentWeek();
        $totalWeeks = $this->totalWeeks();

        if ($currentWeek > $totalWeeks) {
            return response()->json([
                'message' => 'League is already finished.',
            ], 400);
        }

        $results = [];

        for ($week = $currentWeek; $week <= $totalWeeks; $week++) {
            $matches = $this->simulationService->simulateWeek($week);
            $results[] = [
                'week' => $week,
                'matches' => $matches,
            ];
        }

        return response()->json([
            'results' => $results,
            'league_table' => $this->leagueTableService->calculate(),
            'predictions' => $this->predictionService->predict(),
            'is_finished' => true,
        ]);
    }

    /**
     * POST /api/league/reset
     * Reset the league and generate new fixtures.
     */
    public function reset(): JsonResponse
    {
        $this->fixtureService->generate();

        return response()->json([
            'message' => 'League has been reset.',
            'league_table' => $this->leagueTableService->calculate(),
            'matches' => $this->fixtureService->getAllMatches(),
        ]);
    }

    /**
     * PUT /api/matches/{id}
     * Edit a match result and recalculate standings.
     */
    public function updateMatch(UpdateMatchRequest $request, int $id): JsonResponse
    {
        $match = $this->simulationService->updateMatchScore(
            $id,
            $request->validated('home_goals'),
            $request->validated('away_goals'),
        );

        return response()->json([
            'match' => $match,
            'league_table' => $this->leagueTableService->calculate(),
            'predictions' => $this->predictionService->predict(),
        ]);
    }
}

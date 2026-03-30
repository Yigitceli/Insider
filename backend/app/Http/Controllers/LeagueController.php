<?php

namespace App\Http\Controllers;

use App\Repositories\Interfaces\MatchRepositoryInterface;
use App\Services\FixtureService;
use App\Services\LeagueTableService;
use App\Services\PredictionService;
use App\Services\SimulationService;
use App\Http\Requests\UpdateMatchRequest;
use Illuminate\Http\JsonResponse;

class LeagueController extends Controller
{
    private const TOTAL_WEEKS = 6;

    public function __construct(
        private MatchRepositoryInterface $matchRepository,
        private FixtureService $fixtureService,
        private SimulationService $simulationService,
        private LeagueTableService $leagueTableService,
        private PredictionService $predictionService,
    ) {}

    /**
     * GET /api/league
     * Returns league table, current week matches, and predictions.
     */
    public function index(): JsonResponse
    {
        $currentWeek = $this->matchRepository->getCurrentWeek();
        $lastPlayedWeek = $currentWeek - 1;

        return response()->json([
            'league_table' => $this->leagueTableService->calculate(),
            'current_week' => $currentWeek,
            'last_played_week' => $lastPlayedWeek > 0 ? $lastPlayedWeek : null,
            'matches' => $lastPlayedWeek > 0
                ? $this->matchRepository->getByWeek($lastPlayedWeek)
                : [],
            'predictions' => $this->predictionService->predict(),
            'is_finished' => $currentWeek > self::TOTAL_WEEKS,
        ]);
    }

    /**
     * POST /api/league/next-week
     * Simulate the next week's matches.
     */
    public function nextWeek(): JsonResponse
    {
        $currentWeek = $this->matchRepository->getCurrentWeek();

        if ($currentWeek > self::TOTAL_WEEKS) {
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
            'is_finished' => $currentWeek >= self::TOTAL_WEEKS,
        ]);
    }

    /**
     * POST /api/league/play-all
     * Simulate all remaining weeks.
     */
    public function playAll(): JsonResponse
    {
        $currentWeek = $this->matchRepository->getCurrentWeek();

        if ($currentWeek > self::TOTAL_WEEKS) {
            return response()->json([
                'message' => 'League is already finished.',
            ], 400);
        }

        $results = [];

        for ($week = $currentWeek; $week <= self::TOTAL_WEEKS; $week++) {
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
            'matches' => $this->matchRepository->all(),
        ]);
    }

    /**
     * PUT /api/matches/{id}
     * Edit a match result and recalculate standings.
     */
    public function updateMatch(UpdateMatchRequest $request, int $id): JsonResponse
    {
        $match = $this->matchRepository->updateScore(
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

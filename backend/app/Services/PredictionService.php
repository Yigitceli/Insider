<?php

namespace App\Services;

use App\Repositories\Interfaces\MatchRepositoryInterface;
use App\Repositories\Interfaces\TeamRepositoryInterface;

class PredictionService
{
    private const PREDICTION_THRESHOLD = 0.6; // Show predictions after 60% of league played

    public function __construct(
        private MatchRepositoryInterface $matchRepository,
        private TeamRepositoryInterface $teamRepository,
        private LeagueTableService $leagueTableService,
        private FixtureService $fixtureService,
    ) {}

    /**
     * Calculate championship prediction percentages for each team.
     * Returns null if predictions shouldn't be shown yet.
     */
    public function predict(): ?array
    {
        $totalWeeks = $this->fixtureService->getTotalWeeks();

        if ($totalWeeks === 0) {
            return null;
        }

        $currentWeek = $this->matchRepository->getCurrentWeek();
        $playedWeeks = $currentWeek - 1;

        // Show predictions after 60% of league is played
        $predictionStartAfter = (int) ceil($totalWeeks * self::PREDICTION_THRESHOLD);

        if ($playedWeeks < $predictionStartAfter) {
            return null;
        }

        $standings = $this->leagueTableService->calculate();
        $remainingWeeks = $totalWeeks - $playedWeeks;
        $maxRemainingPoints = $remainingWeeks * 3;

        // If league is over, leader is champion
        if ($remainingWeeks <= 0) {
            return $this->finalStandings($standings);
        }

        // Add max possible points to each team
        foreach ($standings as &$standing) {
            $standing['max_possible'] = $standing['PTS'] + $maxRemainingPoints;
        }
        unset($standing);

        // If leader can't be caught mathematically
        if ($standings[0]['PTS'] > $standings[1]['max_possible']) {
            return $this->finalStandings($standings);
        }

        // Build team strength map for weighted prediction
        $teams = $this->teamRepository->all();
        $strengthMap = [];
        foreach ($teams as $team) {
            $strengthMap[$team->id] = $team->strength;
        }

        // Calculate weighted probability
        $predictions = [];
        $totalWeight = 0;

        foreach ($standings as $standing) {
            $teamStrength = $strengthMap[$standing['team_id']] ?? 50;

            $pointsWeight = $standing['PTS'] * 3;
            $gdWeight = max(0, $standing['GD'] + 10);
            $strengthWeight = ($teamStrength / 100) * $remainingWeeks * 2;
            $ceilingWeight = $standing['max_possible'];

            $weight = $pointsWeight + $gdWeight + $strengthWeight + $ceilingWeight;

            $predictions[$standing['team_id']] = [
                'team_id' => $standing['team_id'],
                'team_name' => $standing['team_name'],
                'weight' => $weight,
            ];

            $totalWeight += $weight;
        }

        // Convert weights to percentages
        foreach ($predictions as &$prediction) {
            $prediction['percentage'] = $totalWeight > 0
                ? (int) round(($prediction['weight'] / $totalWeight) * 100)
                : 0;
            unset($prediction['weight']);
        }
        unset($prediction);

        $this->normalizePercentages($predictions);

        usort($predictions, fn($a, $b) => $b['percentage'] - $a['percentage']);

        return $predictions;
    }

    private function finalStandings(array $standings): array
    {
        $predictions = [];
        $isFirst = true;

        foreach ($standings as $standing) {
            $predictions[] = [
                'team_id' => $standing['team_id'],
                'team_name' => $standing['team_name'],
                'percentage' => $isFirst ? 100 : 0,
            ];
            $isFirst = false;
        }

        return $predictions;
    }

    private function normalizePercentages(array &$predictions): void
    {
        $total = array_sum(array_column($predictions, 'percentage'));

        if ($total === 0 || $total === 100) {
            return;
        }

        $diff = 100 - $total;
        $maxKey = null;
        $maxVal = -1;

        foreach ($predictions as $key => $prediction) {
            if ($prediction['percentage'] > $maxVal) {
                $maxVal = $prediction['percentage'];
                $maxKey = $key;
            }
        }

        if ($maxKey !== null) {
            $predictions[$maxKey]['percentage'] += $diff;
        }
    }
}

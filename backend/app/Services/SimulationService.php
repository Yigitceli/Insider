<?php

namespace App\Services;

use App\Models\FootballMatch;
use App\Repositories\Interfaces\MatchRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SimulationService
{
    private const HOME_ADVANTAGE = 1.1; // 10% bonus for home team

    public function __construct(
        private MatchRepositoryInterface $matchRepository,
    ) {}

    public function simulateWeek(int $week): Collection
    {
        $matches = $this->matchRepository->getByWeek($week);

        foreach ($matches as $match) {
            if (!$match->is_played) {
                $this->simulateMatch($match);
            }
        }

        return $this->matchRepository->getByWeek($week);
    }

    public function simulateMatch(FootballMatch $match): FootballMatch
    {
        $homeStrength = $match->homeTeam->strength * self::HOME_ADVANTAGE;
        $awayStrength = $match->awayTeam->strength;

        $homeGoals = $this->generateGoals($homeStrength);
        $awayGoals = $this->generateGoals($awayStrength);

        return $this->matchRepository->updateScore($match->id, $homeGoals, $awayGoals);
    }

    /**
     * Generate goals based on team strength using Poisson-like distribution.
     * Strength 80-85 → average ~1.5-2.0 goals
     */
    protected function generateGoals(float $strength): int
    {
        // Convert strength (0-100) to expected goals (lambda for Poisson)
        $lambda = ($strength / 100) * 2.5;

        return $this->poissonRandom($lambda);
    }

    /**
     * Generate a Poisson-distributed random number.
     */
    protected function poissonRandom(float $lambda): int
    {
        $l = exp(-$lambda);
        $k = 0;
        $p = 1.0;

        do {
            $k++;
            $p *= mt_rand() / mt_getrandmax();
        } while ($p > $l);

        return $k - 1;
    }
}

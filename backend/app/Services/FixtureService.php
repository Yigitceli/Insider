<?php

namespace App\Services;

use App\Repositories\Interfaces\MatchRepositoryInterface;
use App\Repositories\Interfaces\TeamRepositoryInterface;
use InvalidArgumentException;

class FixtureService
{
    public function __construct(
        private TeamRepositoryInterface $teamRepository,
        private MatchRepositoryInterface $matchRepository,
    ) {}

    public function getTeams(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->teamRepository->all();
    }

    public function getAllMatches(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->matchRepository->all();
    }

    /**
     * Calculate total weeks based on team count.
     * Double round-robin: each pair plays twice (home + away).
     */
    public function getTotalWeeks(): int
    {
        $count = $this->teamRepository->all()->count();

        if ($count < 2) {
            return 0;
        }

        // For odd teams, add bye → effective count is count+1
        $effective = $count % 2 === 0 ? $count : $count + 1;

        // Single leg = effective - 1 weeks, double leg = * 2
        return ($effective - 1) * 2;
    }

    public function generate(): void
    {
        $teams = $this->teamRepository->all();
        $teamIds = $teams->pluck('id')->toArray();
        $count = count($teamIds);

        if ($count < 2) {
            throw new InvalidArgumentException('At least 2 teams are required to generate fixtures.');
        }

        $this->matchRepository->deleteAll();

        $firstLeg = $this->generateRoundRobin($teamIds);
        $firstLegWeeks = count($firstLeg);

        // First leg
        foreach ($firstLeg as $week => $matches) {
            foreach ($matches as [$home, $away]) {
                $this->matchRepository->create([
                    'week' => $week + 1,
                    'home_team_id' => $home,
                    'away_team_id' => $away,
                ]);
            }
        }

        // Second leg (swap home/away)
        foreach ($firstLeg as $week => $matches) {
            foreach ($matches as [$home, $away]) {
                $this->matchRepository->create([
                    'week' => $week + $firstLegWeeks + 1,
                    'home_team_id' => $away,
                    'away_team_id' => $home,
                ]);
            }
        }
    }

    /**
     * Round-robin algorithm for N teams.
     * Supports both even and odd team counts.
     * For odd teams, a BYE is added — those matches are skipped.
     */
    private function generateRoundRobin(array $teamIds): array
    {
        $count = count($teamIds);
        // Odd number of teams: add a BYE placeholder
        if ($count % 2 !== 0) {
            $teamIds[] = null; // null = BYE
            $count++;
        }

        $rounds = $count - 1;
        $matchesPerRound = $count / 2;

        $teams = $teamIds;
        $fixed = array_shift($teams);

        $weeks = [];

        for ($round = 0; $round < $rounds; $round++) {
            $matches = [];

            // First match: fixed team vs first in rotation
            $pair = [$fixed, $teams[0]];
            if ($pair[0] !== null && $pair[1] !== null) {
                $matches[] = $pair;
            }

            // Remaining matches: pair from outside in
            for ($i = 1; $i < $matchesPerRound; $i++) {
                $home = $teams[$i];
                $away = $teams[$count - 2 - $i + 1];
                if ($home !== null && $away !== null) {
                    $matches[] = [$home, $away];
                }
            }

            $weeks[] = $matches;

            // Rotate: move last element to front
            array_unshift($teams, array_pop($teams));
        }

        return $weeks;
    }
}

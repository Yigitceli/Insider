<?php

namespace App\Services;

use App\Repositories\Interfaces\MatchRepositoryInterface;
use App\Repositories\Interfaces\TeamRepositoryInterface;

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

    public function generate(): void
    {
        $this->matchRepository->deleteAll();

        $teams = $this->teamRepository->all();
        $teamIds = $teams->pluck('id')->toArray();

        $firstLeg = $this->generateRoundRobin($teamIds);

        // First leg: weeks 1-3
        foreach ($firstLeg as $week => $matches) {
            foreach ($matches as [$home, $away]) {
                $this->matchRepository->create([
                    'week' => $week + 1,
                    'home_team_id' => $home,
                    'away_team_id' => $away,
                ]);
            }
        }

        // Second leg: weeks 4-6 (swap home/away)
        foreach ($firstLeg as $week => $matches) {
            foreach ($matches as [$home, $away]) {
                $this->matchRepository->create([
                    'week' => $week + 4,
                    'home_team_id' => $away,
                    'away_team_id' => $home,
                ]);
            }
        }
    }

    /**
     * Round-robin algorithm for N teams.
     * Returns array of weeks, each containing match pairs [home_id, away_id].
     */
    private function generateRoundRobin(array $teamIds): array
    {
        $count = count($teamIds);
        $rounds = $count - 1;
        $matchesPerRound = $count / 2;

        $teams = $teamIds;
        // Fix first team, rotate the rest
        $fixed = array_shift($teams);

        $weeks = [];

        for ($round = 0; $round < $rounds; $round++) {
            $matches = [];

            // First match: fixed team vs first in rotation
            $matches[] = [$fixed, $teams[0]];

            // Remaining matches: pair from outside in
            for ($i = 1; $i < $matchesPerRound; $i++) {
                $home = $teams[$i];
                $away = $teams[$count - 2 - $i + 1];
                $matches[] = [$home, $away];
            }

            $weeks[] = $matches;

            // Rotate: move last element to front
            array_unshift($teams, array_pop($teams));
        }

        return $weeks;
    }
}

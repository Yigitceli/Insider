<?php

namespace App\Services;

use App\Repositories\Interfaces\MatchRepositoryInterface;
use App\Repositories\Interfaces\TeamRepositoryInterface;

class LeagueTableService
{
    public function __construct(
        private TeamRepositoryInterface $teamRepository,
        private MatchRepositoryInterface $matchRepository,
    ) {}

    /**
     * Calculate league standings from played matches.
     * Returns sorted array of team standings.
     */
    public function calculate(): array
    {
        $teams = $this->teamRepository->all();
        $playedMatches = $this->matchRepository->getPlayed();

        $standings = [];

        foreach ($teams as $team) {
            $standings[$team->id] = [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'PTS' => 0,
                'P' => 0,
                'W' => 0,
                'D' => 0,
                'L' => 0,
                'GF' => 0,  // Goals for
                'GA' => 0,  // Goals against
                'GD' => 0,  // Goal difference
            ];
        }

        foreach ($playedMatches as $match) {
            $homeId = $match->home_team_id;
            $awayId = $match->away_team_id;

            // Games played
            $standings[$homeId]['P']++;
            $standings[$awayId]['P']++;

            // Goals
            $standings[$homeId]['GF'] += $match->home_goals;
            $standings[$homeId]['GA'] += $match->away_goals;
            $standings[$awayId]['GF'] += $match->away_goals;
            $standings[$awayId]['GA'] += $match->home_goals;

            // Points
            if ($match->home_goals > $match->away_goals) {
                // Home win
                $standings[$homeId]['W']++;
                $standings[$homeId]['PTS'] += 3;
                $standings[$awayId]['L']++;
            } elseif ($match->home_goals < $match->away_goals) {
                // Away win
                $standings[$awayId]['W']++;
                $standings[$awayId]['PTS'] += 3;
                $standings[$homeId]['L']++;
            } else {
                // Draw
                $standings[$homeId]['D']++;
                $standings[$homeId]['PTS'] += 1;
                $standings[$awayId]['D']++;
                $standings[$awayId]['PTS'] += 1;
            }
        }

        // Calculate goal difference
        foreach ($standings as &$standing) {
            $standing['GD'] = $standing['GF'] - $standing['GA'];
        }

        // Sort: PTS desc → GD desc → GF desc
        usort($standings, function ($a, $b) {
            if ($a['PTS'] !== $b['PTS']) return $b['PTS'] - $a['PTS'];
            if ($a['GD'] !== $b['GD']) return $b['GD'] - $a['GD'];
            return $b['GF'] - $a['GF'];
        });

        return $standings;
    }
}

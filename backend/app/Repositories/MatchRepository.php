<?php

namespace App\Repositories;

use App\Models\FootballMatch;
use App\Repositories\Interfaces\MatchRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class MatchRepository implements MatchRepositoryInterface
{
    public function all(): Collection
    {
        return FootballMatch::with(['homeTeam', 'awayTeam'])->orderBy('week')->get();
    }

    public function find(int $id): FootballMatch
    {
        return FootballMatch::with(['homeTeam', 'awayTeam'])->findOrFail($id);
    }

    public function getByWeek(int $week): Collection
    {
        return FootballMatch::with(['homeTeam', 'awayTeam'])
            ->where('week', $week)
            ->get();
    }

    public function getPlayed(): Collection
    {
        return FootballMatch::with(['homeTeam', 'awayTeam'])
            ->where('is_played', true)
            ->orderBy('week')
            ->get();
    }

    public function getUnplayed(): Collection
    {
        return FootballMatch::with(['homeTeam', 'awayTeam'])
            ->where('is_played', false)
            ->orderBy('week')
            ->get();
    }

    public function getCurrentWeek(): int
    {
        $lastPlayedWeek = FootballMatch::where('is_played', true)->max('week');

        return $lastPlayedWeek ? $lastPlayedWeek + 1 : 1;
    }

    public function create(array $data): FootballMatch
    {
        return FootballMatch::create($data);
    }

    public function updateScore(int $id, int $homeGoals, int $awayGoals): FootballMatch
    {
        $match = FootballMatch::findOrFail($id);
        $match->update([
            'home_goals' => $homeGoals,
            'away_goals' => $awayGoals,
            'is_played' => true,
        ]);

        return $match->load(['homeTeam', 'awayTeam']);
    }

    public function deleteAll(): void
    {
        FootballMatch::query()->delete();
    }
}

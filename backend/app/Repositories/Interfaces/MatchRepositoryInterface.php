<?php

namespace App\Repositories\Interfaces;

use App\Models\FootballMatch;
use Illuminate\Database\Eloquent\Collection;

interface MatchRepositoryInterface
{
    public function all(): Collection;

    public function find(int $id): FootballMatch;

    public function getByWeek(int $week): Collection;

    public function getPlayed(): Collection;

    public function getUnplayed(): Collection;

    public function getCurrentWeek(): int;

    public function create(array $data): FootballMatch;

    public function updateScore(int $id, int $homeGoals, int $awayGoals): FootballMatch;

    public function deleteAll(): void;
}

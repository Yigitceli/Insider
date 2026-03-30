<?php

namespace App\Repositories;

use App\Models\Team;
use App\Repositories\Interfaces\TeamRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class TeamRepository implements TeamRepositoryInterface
{
    public function all(): Collection
    {
        return Team::all();
    }

    public function find(int $id): Team
    {
        return Team::findOrFail($id);
    }
}

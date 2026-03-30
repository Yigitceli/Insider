<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use App\Models\Team;

interface TeamRepositoryInterface
{
    public function all(): Collection;

    public function find(int $id): Team;
}

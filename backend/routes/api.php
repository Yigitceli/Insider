<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\LeagueController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::get('/teams', [LeagueController::class, 'teams']);
Route::get('/fixtures', [LeagueController::class, 'fixtures']);
Route::get('/league', [LeagueController::class, 'index']);
Route::post('/league/next-week', [LeagueController::class, 'nextWeek']);
Route::post('/league/play-all', [LeagueController::class, 'playAll']);
Route::post('/league/reset', [LeagueController::class, 'reset']);
Route::put('/matches/{id}', [LeagueController::class, 'updateMatch']);

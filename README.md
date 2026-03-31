# Insider One Champions League

A football league simulation built with Laravel (PHP) and Vue.js. Simulates matches week by week, calculates league standings, and predicts championship probabilities.

## Tech Stack

- **Backend:** Laravel 13 (PHP 8.4)
- **Frontend:** Vue.js 3 + Vite + Vue Router
- **Database:** MySQL 8.0
- **Containerization:** Docker + Docker Compose

## Architecture

```
Backend (Repository + Service Pattern):
Controller → Service → Repository Interface → Repository → Eloquent Model

Frontend (Component Design Pattern):
App.vue → Vue Router → TeamList / FixtureView / SimulationView
```

### Backend Structure
```
app/
├── Http/Controllers/       # HTTP layer (no business logic)
├── Http/Requests/          # Form validation (UpdateMatchRequest)
├── Services/               # Business logic
│   ├── FixtureService      # Round-robin fixture generation
│   ├── SimulationService   # Poisson-based match simulation
│   ├── LeagueTableService  # Standings calculation (PTS → GD → GF)
│   └── PredictionService   # Championship probability prediction
├── Repositories/           # Database access layer
│   ├── Interfaces/         # Contracts for DI
│   ├── TeamRepository
│   └── MatchRepository
├── Models/                 # Eloquent ORM
│   ├── Team
│   └── FootballMatch
└── Providers/
    └── AppServiceProvider  # Singleton bindings
```

### Frontend Structure
```
src/
├── components/
│   ├── TeamList.vue        # Screen 1: Team listing
│   ├── FixtureView.vue     # Screen 2: 6-week fixture grid
│   ├── SimulationView.vue  # Screen 3: Simulation orchestrator
│   ├── LeagueTable.vue     # Standings table
│   ├── MatchResults.vue    # Match scores (editable)
│   ├── PredictionTable.vue # Championship percentages
│   └── ActionButtons.vue   # Play All / Next Week / Reset
├── services/
│   └── api.js              # API communication layer
├── router.js               # Vue Router (3 routes)
├── App.vue                 # Root layout
└── main.js                 # Entry point
```

## Getting Started

### Prerequisites
- Docker
- Docker Compose

### Run
```bash
docker-compose up -d --build
```

This starts 5 services in order:
```
db (MySQL) → migrate → seed → app (Laravel :8000) → frontend (Vue :5173)
```

- **Frontend:** http://localhost:5173
- **Backend API:** http://localhost:8000/api

### Run Tests
```bash
docker-compose run --rm test
```

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/health` | Health check with DB status |
| GET | `/api/teams` | List all teams |
| GET | `/api/fixtures` | All matches (fixture view) |
| GET | `/api/league` | League table + matches + predictions |
| POST | `/api/league/next-week` | Simulate next week |
| POST | `/api/league/play-all` | Simulate all remaining weeks |
| POST | `/api/league/reset` | Reset league, regenerate fixtures |
| PUT | `/api/matches/{id}` | Edit match score |

## Features

### Core
- 4 teams with different strength ratings
- Poisson-distributed match simulation with home advantage
- Premier League scoring rules (3-1-0, sorted by PTS → GD → GF)
- Week-by-week simulation with league table updates
- Championship predictions after 60% of league played

### Bonus
- **Play All:** Simulates all remaining weeks, lists results by week
- **Match Editing:** Click any score to edit, standings recalculate instantly

### Technical
- Dynamic team count support (2-6+ teams, even/odd)
- Centralized JSON error handling (404, 422)
- Singleton DI bindings for performance
- Eager loading to prevent N+1 queries
- 49 automated tests (unit + feature)

## Simulation Algorithm

Match scores are generated using a **Poisson distribution**:

```
lambda = (team_strength / 100) * 2.5
home_lambda *= 1.1  (home advantage)
```

This produces realistic scores where stronger teams generally win, but upsets can happen — matching real football dynamics.

## Prediction Algorithm

Championship probabilities are calculated using weighted factors:
- **Current points** (highest weight)
- **Goal difference** (form indicator)
- **Team strength** (affects remaining matches)
- **Maximum possible points** (mathematical ceiling)

If a team is mathematically uncatchable, it gets 100%.

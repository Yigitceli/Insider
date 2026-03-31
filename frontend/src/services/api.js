const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000'

async function request(method, path, body = null) {
  const options = {
    method,
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
  }

  if (body) {
    options.body = JSON.stringify(body)
  }

  const response = await fetch(`${API_URL}${path}`, options)
  const data = await response.json()

  if (!response.ok) {
    throw new Error(data.message || 'API request failed')
  }

  return data
}

export default {
  getTeams() {
    return request('GET', '/api/teams')
  },

  getFixtures() {
    return request('GET', '/api/fixtures')
  },

  getLeague() {
    return request('GET', '/api/league')
  },

  nextWeek() {
    return request('POST', '/api/league/next-week')
  },

  playAll() {
    return request('POST', '/api/league/play-all')
  },

  reset() {
    return request('POST', '/api/league/reset')
  },

  updateMatch(id, homeGoals, awayGoals) {
    return request('PUT', `/api/matches/${id}`, {
      home_goals: homeGoals,
      away_goals: awayGoals,
    })
  },
}

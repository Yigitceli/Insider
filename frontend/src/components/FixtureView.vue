<template>
  <div class="fixture-view">
    <h2>Fixtures</h2>
    <div v-if="loading" class="loading">Loading fixtures...</div>
    <template v-else>
      <div class="weeks-grid">
        <div v-for="week in weeks" :key="week.number" class="week-card">
          <div class="week-header">Week {{ week.number }}</div>
          <div v-for="match in week.matches" :key="match.id" class="match-row">
            <span class="home">{{ match.home_team.name }}</span>
            <span class="separator">-</span>
            <span class="away">{{ match.away_team.name }}</span>
          </div>
        </div>
      </div>
      <button
        class="btn btn-start"
        @click="$router.push({ name: 'simulation' })"
      >
        Start Simulation
      </button>
    </template>
    <div v-if="error" class="error">{{ error }}</div>
  </div>
</template>

<script>
import api from '../services/api.js'

export default {
  name: 'FixtureView',
  data() {
    return {
      allMatches: [],
      loading: false,
      error: null,
    }
  },
  computed: {
    weeks() {
      const grouped = {}
      this.allMatches.forEach((match) => {
        if (!grouped[match.week]) {
          grouped[match.week] = { number: match.week, matches: [] }
        }
        grouped[match.week].matches.push(match)
      })
      return Object.values(grouped).sort((a, b) => a.number - b.number)
    },
  },
  async mounted() {
    this.loading = true
    try {
      const data = await api.getFixtures()
      this.allMatches = data.matches
    } catch (e) {
      this.error = e.message
    } finally {
      this.loading = false
    }
  },
}
</script>

<style scoped>
.fixture-view {
  max-width: 1000px;
  margin: 0 auto;
}

.loading {
  text-align: center;
  color: #999;
  padding: 40px;
}

.weeks-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.week-card {
  border: 1px solid #ddd;
  border-radius: 6px;
  overflow: hidden;
}

.week-header {
  background-color: #2c3e50;
  color: white;
  padding: 10px 12px;
  font-weight: bold;
}

.match-row {
  display: flex;
  justify-content: space-between;
  padding: 10px 12px;
  border-bottom: 1px solid #eee;
}

.match-row:last-child {
  border-bottom: none;
}

.home {
  flex: 1;
  text-align: left;
}

.separator {
  padding: 0 8px;
  color: #999;
}

.away {
  flex: 1;
  text-align: right;
}

.btn-start {
  padding: 10px 24px;
  font-size: 14px;
  font-weight: bold;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  background-color: #5ab0c8;
  color: white;
  transition: opacity 0.2s;
}

.btn-start:hover:not(:disabled) {
  opacity: 0.85;
}

.error {
  text-align: center;
  color: #e74c3c;
  padding: 10px;
  margin-top: 10px;
  background-color: #fdeaea;
  border-radius: 6px;
}
</style>

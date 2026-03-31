<template>
  <div class="simulation-view">
    <div class="top-row">
      <LeagueTable :standings="leagueTable" />
      <PredictionTable
        :predictions="predictions"
        :week-label="weekLabel"
      />
    </div>

    <ActionButtons
      :is-finished="isFinished"
      :loading="loading"
      @play-all="handlePlayAll"
      @next-week="handleNextWeek"
      @reset="handleReset"
    />

    <div class="match-section">
      <h2>Match Results</h2>
      <div v-if="playedWeeks.length" class="weeks-list">
        <MatchResults
          v-for="weekResult in playedWeeks"
          :key="weekResult.week"
          :matches="weekResult.matches"
          :week-label="weekResult.week + ordinalSuffix(weekResult.week) + ' Week'"
          @update-match="handleUpdateMatch"
        />
      </div>
      <p v-else class="no-matches">No matches played yet.</p>
    </div>

    <div v-if="error" class="error">{{ error }}</div>
  </div>
</template>

<script>
import LeagueTable from './LeagueTable.vue'
import MatchResults from './MatchResults.vue'
import PredictionTable from './PredictionTable.vue'
import ActionButtons from './ActionButtons.vue'
import api from '../services/api.js'

export default {
  name: 'SimulationView',
  components: {
    LeagueTable,
    MatchResults,
    PredictionTable,
    ActionButtons,
  },
  data() {
    return {
      leagueTable: [],
      playedWeeks: [],
      predictions: null,
      currentWeek: 1,
      lastPlayedWeek: null,
      isFinished: false,
      loading: false,
      error: null,
    }
  },
  computed: {
    weekLabel() {
      if (!this.lastPlayedWeek) return ''
      return `${this.lastPlayedWeek}${this.ordinalSuffix(this.lastPlayedWeek)} Week`
    },
  },
  async mounted() {
    await this.fetchLeague()
  },
  methods: {
    ordinalSuffix(n) {
      const s = ['th', 'st', 'nd', 'rd']
      const v = n % 100
      return s[(v - 20) % 10] || s[v] || s[0]
    },

    async fetchLeague() {
      this.loading = true
      this.error = null
      try {
        const data = await api.getLeague()
        this.leagueTable = data.league_table
        this.predictions = data.predictions
        this.currentWeek = data.current_week
        this.lastPlayedWeek = data.last_played_week
        this.isFinished = data.is_finished
        await this.loadPlayedWeeks()
      } catch (e) {
        this.error = e.message
      } finally {
        this.loading = false
      }
    },

    async loadPlayedWeeks() {
      if (!this.lastPlayedWeek) {
        this.playedWeeks = []
        return
      }
      const data = await api.getFixtures()
      const grouped = {}
      data.matches.forEach((match) => {
        if (match.is_played) {
          if (!grouped[match.week]) {
            grouped[match.week] = { week: match.week, matches: [] }
          }
          grouped[match.week].matches.push(match)
        }
      })
      this.playedWeeks = Object.values(grouped).sort((a, b) => a.week - b.week)
    },

    async handleNextWeek() {
      this.loading = true
      this.error = null
      try {
        const data = await api.nextWeek()
        this.leagueTable = data.league_table
        this.predictions = data.predictions
        this.lastPlayedWeek = data.week
        this.currentWeek = data.week + 1
        this.isFinished = data.is_finished
        this.playedWeeks.push({
          week: data.week,
          matches: data.matches,
        })
      } catch (e) {
        this.error = e.message
      } finally {
        this.loading = false
      }
    },

    async handlePlayAll() {
      this.loading = true
      this.error = null
      try {
        const data = await api.playAll()
        this.leagueTable = data.league_table
        this.predictions = data.predictions
        this.isFinished = data.is_finished
        for (const result of data.results) {
          this.playedWeeks.push({
            week: result.week,
            matches: result.matches,
          })
        }
        if (data.results.length > 0) {
          const lastWeek = data.results[data.results.length - 1]
          this.lastPlayedWeek = lastWeek.week
          this.currentWeek = lastWeek.week + 1
        }
      } catch (e) {
        this.error = e.message
      } finally {
        this.loading = false
      }
    },

    async handleReset() {
      this.loading = true
      this.error = null
      try {
        await api.reset()
        this.$router.push({ name: 'fixtures' })
      } catch (e) {
        this.error = e.message
      } finally {
        this.loading = false
      }
    },

    async handleUpdateMatch(matchId, homeGoals, awayGoals) {
      this.loading = true
      this.error = null
      try {
        const data = await api.updateMatch(matchId, homeGoals, awayGoals)
        this.leagueTable = data.league_table
        this.predictions = data.predictions
        for (const weekResult of this.playedWeeks) {
          const idx = weekResult.matches.findIndex((m) => m.id === matchId)
          if (idx !== -1) {
            weekResult.matches[idx] = data.match
            break
          }
        }
      } catch (e) {
        this.error = e.message
      } finally {
        this.loading = false
      }
    },
  },
}
</script>

<style scoped>
.top-row {
  display: flex;
  gap: 20px;
  margin-bottom: 20px;
}

.match-section {
  margin-bottom: 20px;
}

.match-section h2 {
  color: #2c3e50;
  margin-bottom: 16px;
}

.weeks-list {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.no-matches {
  color: #999;
  font-style: italic;
}

.error {
  text-align: center;
  color: #e74c3c;
  padding: 10px;
  margin-top: 10px;
  background-color: #fdeaea;
  border-radius: 6px;
}

@media (max-width: 768px) {
  .top-row {
    flex-direction: column;
  }

  .weeks-list {
    grid-template-columns: 1fr;
  }
}
</style>

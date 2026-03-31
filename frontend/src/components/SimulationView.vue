<template>
  <div class="simulation-view">
    <div class="panels">
      <LeagueTable :standings="leagueTable" />
      <MatchResults
        :matches="matches"
        :week-label="weekLabel"
        @update-match="handleUpdateMatch"
      />
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
      matches: [],
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
        this.updateState(data)
      } catch (e) {
        this.error = e.message
      } finally {
        this.loading = false
      }
    },

    async handleNextWeek() {
      this.loading = true
      this.error = null
      try {
        const data = await api.nextWeek()
        this.leagueTable = data.league_table
        this.matches = data.matches
        this.predictions = data.predictions
        this.lastPlayedWeek = data.week
        this.currentWeek = data.week + 1
        this.isFinished = data.is_finished
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
        if (data.results.length > 0) {
          const lastWeek = data.results[data.results.length - 1]
          this.matches = lastWeek.matches
          this.lastPlayedWeek = lastWeek.week
        }
        this.currentWeek = data.results.length > 0
          ? data.results[data.results.length - 1].week + 1
          : this.currentWeek
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
        const idx = this.matches.findIndex((m) => m.id === matchId)
        if (idx !== -1) {
          this.matches[idx] = data.match
        }
      } catch (e) {
        this.error = e.message
      } finally {
        this.loading = false
      }
    },

    updateState(data) {
      this.leagueTable = data.league_table
      this.matches = data.matches || []
      this.predictions = data.predictions
      this.currentWeek = data.current_week
      this.lastPlayedWeek = data.last_played_week
      this.isFinished = data.is_finished
    },
  },
}
</script>

<style scoped>
.panels {
  display: flex;
  gap: 20px;
  margin-bottom: 20px;
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
  .panels {
    flex-direction: column;
  }
}
</style>

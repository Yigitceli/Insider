<template>
  <div class="team-list">
    <h2>Tournament Teams</h2>
    <table>
      <thead>
        <tr>
          <th>Team Name</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="team in teams" :key="team.id">
          <td>{{ team.name }}</td>
        </tr>
      </tbody>
    </table>
    <button
      class="btn btn-generate"
      :disabled="loading"
      @click="handleGenerate"
    >
      Generate Fixtures
    </button>
    <div v-if="error" class="error">{{ error }}</div>
  </div>
</template>

<script>
import api from '../services/api.js'

export default {
  name: 'TeamList',
  data() {
    return {
      teams: [],
      loading: false,
      error: null,
    }
  },
  async mounted() {
    this.loading = true
    try {
      const data = await api.getTeams()
      this.teams = data.teams
    } catch (e) {
      this.error = e.message
    } finally {
      this.loading = false
    }
  },
  methods: {
    async handleGenerate() {
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
  },
}
</script>

<style scoped>
.team-list {
  max-width: 600px;
  margin: 0 auto;
}

table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 20px;
}

th {
  background-color: #2c3e50;
  color: white;
  padding: 12px;
  text-align: left;
}

td {
  padding: 12px;
  border-bottom: 1px solid #eee;
}

.btn-generate {
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

.btn-generate:hover:not(:disabled) {
  opacity: 0.85;
}

.btn-generate:disabled {
  opacity: 0.5;
  cursor: not-allowed;
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

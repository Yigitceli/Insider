<template>
  <div class="match-results">
    <table>
      <thead>
        <tr>
          <th colspan="3">{{ weekLabel }} Match Result</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="match in matches" :key="match.id" class="match-row">
          <td class="team home">{{ match.home_team.name }}</td>
          <td class="score">
            <template v-if="editingId === match.id">
              <input
                v-model.number="editHome"
                type="number"
                min="0"
                class="score-input"
              />
              -
              <input
                v-model.number="editAway"
                type="number"
                min="0"
                class="score-input"
              />
              <button class="btn-save" @click="saveEdit(match.id)">Save</button>
              <button class="btn-cancel" @click="cancelEdit">Cancel</button>
            </template>
            <template v-else>
              <span
                class="score-text"
                title="Click to edit score"
                @click="startEdit(match)"
              >
                {{ match.home_goals }} - {{ match.away_goals }}
                <span class="edit-icon">&#9998;</span>
              </span>
            </template>
          </td>
          <td class="team away">{{ match.away_team.name }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
export default {
  name: 'MatchResults',
  props: {
    matches: {
      type: Array,
      required: true,
    },
    weekLabel: {
      type: String,
      default: '',
    },
  },
  emits: ['update-match'],
  data() {
    return {
      editingId: null,
      editHome: 0,
      editAway: 0,
    }
  },
  methods: {
    startEdit(match) {
      this.editingId = match.id
      this.editHome = match.home_goals
      this.editAway = match.away_goals
    },
    cancelEdit() {
      this.editingId = null
    },
    saveEdit(matchId) {
      const home = Math.max(0, Math.floor(this.editHome) || 0)
      const away = Math.max(0, Math.floor(this.editAway) || 0)
      this.$emit('update-match', matchId, home, away)
      this.editingId = null
    },
  },
}
</script>

<style scoped>
.match-results {
  flex: 1;
}

.no-matches {
  color: #999;
  font-style: italic;
  padding: 20px 0;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th {
  background-color: #2c3e50;
  color: white;
  padding: 8px;
}

td {
  border: 1px solid #ddd;
  padding: 8px;
}

.team {
  width: 40%;
}

.home {
  text-align: right;
}

.away {
  text-align: left;
}

.score {
  text-align: center;
  width: 20%;
}

.score-text {
  cursor: pointer;
  font-weight: bold;
  padding: 4px 10px;
  border-radius: 4px;
  border: 1px dashed transparent;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.score-text:hover {
  background-color: #e8f4fd;
  border-color: #3498db;
}

.edit-icon {
  font-size: 12px;
  opacity: 0.3;
  transition: opacity 0.2s;
}

.score-text:hover .edit-icon {
  opacity: 0.8;
}

.score-input {
  width: 40px;
  text-align: center;
  padding: 2px;
}

.btn-save, .btn-cancel {
  margin-left: 4px;
  padding: 2px 6px;
  font-size: 12px;
  cursor: pointer;
  border: 1px solid #ccc;
  border-radius: 3px;
}

.btn-save {
  background-color: #27ae60;
  color: white;
  border-color: #27ae60;
}

.btn-cancel {
  background-color: #e74c3c;
  color: white;
  border-color: #e74c3c;
}

tr:nth-child(even) {
  background-color: #f2f2f2;
}
</style>

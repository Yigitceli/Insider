<template>
  <div class="league-table">
    <h2>League Table</h2>
    <table>
      <thead>
        <tr>
          <th>Teams</th>
          <th>PTS</th>
          <th>P</th>
          <th>W</th>
          <th>D</th>
          <th>L</th>
          <th>GD</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="team in standings" :key="team.team_id">
          <td>{{ team.team_name }}</td>
          <td>{{ team.PTS }}</td>
          <td>{{ team.P }}</td>
          <td>{{ team.W }}</td>
          <td>{{ team.D }}</td>
          <td>{{ team.L }}</td>
          <td>{{ team.GD }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
export default {
  name: 'LeagueTable',
  props: {
    standings: {
      type: Array,
      required: true,
    },
  },
}
</script>

<style scoped>
.league-table {
  flex: 1;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th, td {
  border: 1px solid #ddd;
  padding: 8px;
  text-align: center;
}

th {
  background-color: #2c3e50;
  color: white;
}

td:first-child {
  text-align: left;
}

tr:nth-child(even) {
  background-color: #f2f2f2;
}
</style>

<template>
  <div class="prediction-table">
    <h2>Championship Predictions</h2>
    <div v-if="!predictions" class="no-predictions">
      Predictions will appear after the 4th week.
    </div>
    <table v-else>
      <thead>
        <tr>
          <th colspan="2">{{ weekLabel }} Predictions of Championship</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="prediction in predictions" :key="prediction.team_id">
          <td>{{ prediction.team_name }}</td>
          <td class="percentage">%{{ prediction.percentage }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
export default {
  name: 'PredictionTable',
  props: {
    predictions: {
      type: Array,
      default: null,
    },
    weekLabel: {
      type: String,
      default: '',
    },
  },
}
</script>

<style scoped>
.prediction-table {
  flex: 1;
}

.no-predictions {
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

.percentage {
  text-align: center;
  font-weight: bold;
}

tr:nth-child(even) {
  background-color: #f2f2f2;
}
</style>

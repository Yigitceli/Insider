<template>
  <div id="app">
    <h1>Insider One Champions League</h1>
    <p>Status: {{ status }}</p>
  </div>
</template>

<script>
export default {
  name: 'App',
  data() {
    return {
      status: 'loading...',
    }
  },
  async mounted() {
    try {
      const response = await fetch(`${import.meta.env.VITE_API_URL}/api/health`)
      const data = await response.json()
      this.status = data.status
    } catch {
      this.status = 'API unreachable'
    }
  },
}
</script>

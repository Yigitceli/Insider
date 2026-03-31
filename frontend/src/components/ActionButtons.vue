<template>
  <div class="action-buttons">
    <button
      class="btn btn-play-all"
      :disabled="isFinished || loading"
      @click="$emit('play-all')"
    >
      Play All
    </button>
    <button
      class="btn btn-next-week"
      :disabled="isFinished || loading"
      @click="$emit('next-week')"
    >
      Next Week
    </button>
    <button
      class="btn btn-reset"
      :disabled="loading"
      @click="$emit('reset')"
    >
      Reset
    </button>
  </div>
</template>

<script>
export default {
  name: 'ActionButtons',
  props: {
    isFinished: {
      type: Boolean,
      default: false,
    },
    loading: {
      type: Boolean,
      default: false,
    },
  },
  emits: ['play-all', 'next-week', 'reset'],
}
</script>

<style scoped>
.action-buttons {
  display: flex;
  justify-content: center;
  gap: 12px;
  padding: 20px 0;
}

.btn {
  padding: 10px 24px;
  font-size: 14px;
  font-weight: bold;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: opacity 0.2s;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-play-all {
  background-color: #2c3e50;
  color: white;
}

.btn-next-week {
  background-color: #27ae60;
  color: white;
}

.btn-reset {
  background-color: #e74c3c;
  color: white;
}

.btn:hover:not(:disabled) {
  opacity: 0.85;
}
</style>

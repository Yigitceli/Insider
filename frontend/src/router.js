import { createRouter, createWebHistory } from 'vue-router'
import TeamList from './components/TeamList.vue'
import FixtureView from './components/FixtureView.vue'
import SimulationView from './components/SimulationView.vue'

const routes = [
  { path: '/', name: 'teams', component: TeamList },
  { path: '/fixtures', name: 'fixtures', component: FixtureView },
  { path: '/simulate', name: 'simulation', component: SimulationView },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router

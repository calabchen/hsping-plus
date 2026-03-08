import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import Register from '@/views/auth/Register.vue'
import Login from '@/views/auth/Login.vue'
import Dashboard from '@/views/dashboard/Dashboard.vue'
import Profile from '@/views/dashboard/Profile.vue'
import Class from '@/views/dashboard/Class.vue'
import { authStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView,
    },
    {
      path: '/register',
      name: 'register',
      component: Register,
    },
    {
      path: '/login',
      name: 'login',
      component: Login,
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: Dashboard,
      children: [
        {
          path: 'classes',
          name: 'dashboard-classes',
          component: Class,
          meta: { requiresAuth: true },
        },
        {
          path: 'profile',
          name: 'dashboard-profile',
          component: Profile,
          meta: { requiresAuth: true },
        },
      ],
      meta: { requiresAuth: true },
    },
    {
      path: '/about',
      name: 'about',
      // route level code-splitting
      // this generates a separate chunk (About.[hash].js) for this route
      // which is lazy-loaded when the route is visited.
      component: () => import('../views/AboutView.vue'),
    },
  ],
})

router.beforeEach(async (to) => {
  await authStore.initAuth()

  if (!(to.meta?.requiresAuth as boolean) && authStore.state.authRequiredModalVisible) {
    authStore.hideAuthRequiredModal()
  }

  if ((to.meta?.requiresAuth as boolean) && !authStore.isAuthenticated()) {
    authStore.showAuthRequiredModal()
    return false
  }

  if ((to.name === 'login' || to.name === 'register') && authStore.isAuthenticated()) {
    return { name: 'dashboard' }
  }

  return true
})

export default router

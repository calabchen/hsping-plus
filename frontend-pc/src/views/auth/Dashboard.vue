<script setup lang="ts">
import { useRouter } from 'vue-router'
import { authStore } from '@/stores/auth'

const router = useRouter()

const onLogout = async () => {
  await authStore.logout()
  await router.push('/')
}
</script>

<template>
  <div class="dashboard-page">
    <section class="dashboard-card">
      <h1>仪表盘</h1>
      <p>当前登录用户：{{ authStore.state.user?.name }}（{{ authStore.state.user?.email }}）</p>
      <button @click="onLogout">退出登录</button>
    </section>
  </div>
</template>

<style scoped>
.dashboard-page {
  padding: 24px 16px;
}

.dashboard-card {
  max-width: 900px;
  margin: 0 auto;
  background: #ffffff;
  border: 1px solid #d7dee7;
  border-radius: 12px;
  padding: 20px;
}

h1 {
  margin: 0 0 10px;
}

button {
  margin-top: 12px;
  height: 38px;
  border: none;
  border-radius: 8px;
  background: #dc2626;
  color: #fff;
  padding: 0 14px;
  cursor: pointer;
}
</style>
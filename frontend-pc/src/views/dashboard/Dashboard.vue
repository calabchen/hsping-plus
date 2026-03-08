<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'
import { authStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()

const navigateTo = async (id: string) => {
  if (id === 'profile') {
    router.push('/dashboard/profile')
  } else if (id === 'classes') {
    router.push('/dashboard/classes')
  } else if (id === 'logout') {
    await authStore.logout()
    router.push('/')
  } else if (id === 'quizzes') {
    // 待实现：测验管理
    console.log('测验管理功能待开发')
  }
}
</script>

<template>
  <div class="dashboard-container">
    <!-- 左侧边栏 -->
    <aside class="sidebar">
      <nav class="sidebar-nav">
        <!-- 顶部导航项 -->
        <div class="nav-top">
          <a
            href="#"
            @click.prevent="navigateTo('classes')"
            class="nav-item"
            :class="{ 'active': route.path === '/dashboard/classes' }"
          >
            班级管理
          </a>
          <a href="#" @click.prevent="navigateTo('quizzes')" class="nav-item">
            测验管理
          </a>
        </div>

        <!-- 底部导航项 -->
        <div class="nav-bottom">
          <a 
            href="#" 
            @click.prevent="navigateTo('profile')" 
            class="nav-item"
            :class="{ 'active': route.path === '/dashboard/profile' }"
          >
            个人资料
          </a>
          <a href="#" @click.prevent="navigateTo('logout')" class="nav-item logout">
            退出登录
          </a>
        </div>
      </nav>
    </aside>

    <!-- 右侧内容区 -->
    <main class="main-content">
      <RouterView v-if="route.path === '/dashboard/profile' || route.path === '/dashboard/classes'" />
      <div v-else class="content-card content-card-empty">
        <h1>仪表盘</h1>
        <p>欢迎回来，{{ authStore.state.user?.name }}！</p>
      </div>
    </main>
  </div>
</template>

<style scoped>
/* ────────────────────────────────────────────────
   整体布局：左右分栏
   ──────────────────────────────────────────────── */
.dashboard-container {
  display: flex;
  width: 100%;
  height: calc(100vh - 100px); /* 减去 header 高度 */
}

/* ────────────────────────────────────────────────
   左侧边栏：10% 宽度
   ──────────────────────────────────────────────── */
.sidebar {
  width: 10%;
  min-width: 120px;
  background-color: var(--color-background-soft);
  border-right: 1px solid var(--color-border);
  display: flex;
  flex-direction: column;
}

.sidebar-nav {
  display: flex;
  flex-direction: column;
  height: 100%;
  padding: 1rem 0;
}

/* 顶部导航项区域 */
.nav-top {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

/* 底部导航项区域 */
.nav-bottom {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-top: auto;
  padding-top: 1rem;
  border-top: 1px solid var(--color-border);
}

/* 导航项样式 */
.nav-item {
  display: block;
  padding: 0.75rem 1rem;
  color: var(--color-text);
  text-decoration: none;
  text-align: center;
  font-size: 14px;
  transition: background-color 0.2s;
  border-left: 3px solid transparent;
}

.nav-item:hover {
  background-color: var(--color-background-mute);
  border-left-color: var(--color-border-hover);
}

.nav-item.active {
  background-color: var(--color-background-mute);
  border-left-color: #0f766e;
  font-weight: bold;
}

.nav-item.logout {
  color: #dc2626;
}

.nav-item.logout:hover {
  background-color: #fee;
  border-left-color: #dc2626;
}

/* ────────────────────────────────────────────────
   右侧主内容区：90% 宽度
   ──────────────────────────────────────────────── */
.main-content {
  width: 90%;
  height: 100%;
  overflow-y: auto;
  padding: 0;
  background-color: var(--color-background);
  display: flex;
  flex-direction: column;
}

.content-card {
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  border-radius: 8px;
  padding: 20px;
  max-width: 800px;
  margin: 20px;
}

.content-card-empty {
  min-height: 160px;
}

h1 {
  margin: 0 0 10px;
  color: var(--color-heading);
}

p {
  margin: 5px 0;
}

.user-email {
  color: var(--color-text-secondary);
}

/* ────────────────────────────────────────────────
   响应式调整
   ──────────────────────────────────────────────── */
@media (max-width: 768px) {
  .dashboard-container {
    flex-direction: column;
  }

  .sidebar {
    width: 100%;
    min-width: unset;
    height: auto;
    border-right: none;
    border-bottom: 1px solid var(--color-border);
  }

  .sidebar-nav {
    flex-direction: row;
    padding: 0.5rem;
  }

  .nav-top,
  .nav-bottom {
    flex-direction: row;
    border-top: none;
    padding-top: 0;
  }

  .nav-bottom {
    margin-left: auto;
  }

  .nav-item {
    padding: 0.5rem;
    font-size: 12px;
    border-left: none;
    border-bottom: 3px solid transparent;
  }

  .nav-item:hover,
  .nav-item.active {
    border-left-color: transparent;
    border-bottom-color: #0f766e;
  }

  .main-content {
    width: 100%;
  }
}
</style>
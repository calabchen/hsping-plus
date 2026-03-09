<script setup lang="ts">
  import { RouterLink, RouterView } from 'vue-router'
  import { authStore } from '@/stores/auth'

  const closeModal = () => {
    authStore.hideAuthRequiredModal()
  }
</script>

<template>
  <header>
    <img alt="Vue logo" class="logo" src="@/assets/logo.svg" />

    <div class="wrapper">
      <h2>毕设：评卷辅助系统</h2>

      <nav>
        <RouterLink to="/">首页</RouterLink>
        <RouterLink to="/dashboard">仪表盘</RouterLink>
        <RouterLink v-if="!authStore.state.user" to="/login">登录</RouterLink>
        <RouterLink v-if="!authStore.state.user" to="/register">注册</RouterLink>
        <RouterLink to="/about">关于</RouterLink>
      </nav>
    </div>
  </header>

  <RouterView class="router-view-container" /> <!-- 根据当前 URL 路径渲染对应的页面组件 -->

  <!-- 未登录提示弹窗 -->
  <div v-if="authStore.state.authRequiredModalVisible" class="auth-modal-mask" @click.self="closeModal">
    <div class="auth-modal">
      <button class="modal-close" type="button" @click="closeModal" aria-label="关闭弹窗">x</button>
      <p>未登录，请先登录或者注册</p>
      <div class="modal-actions">
        <RouterLink class="action-btn" to="/login" @click="closeModal">去登录</RouterLink>
        <RouterLink class="action-btn secondary" to="/register" @click="closeModal">去注册</RouterLink>
      </div>
    </div>
  </div>
</template>

<style scoped>

  /* 全局布局与根元素 */
  header {
    line-height: 1.5;
    padding: 1rem 2rem;
    border-bottom: 1px solid var(--color-border);
    display: flex;
    align-items: center;
    gap: 2rem;
    flex-shrink: 0;
  }

  .logo {
    flex-shrink: 0;
    width: 60px;
    height: 60px;
  }

  .wrapper {
    display: flex;
    align-items: center;
    gap: 2rem;
    flex: 1;
  }

  /* 导航栏 */
  nav {
    font-size: 14px;
    margin-left: auto;
    display: flex;
  }

  nav a {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-right: 1px solid var(--color-border);
  }

  nav a:last-of-type {
    border-right: none;
  }

  nav a.router-link-exact-active {
    color: var(--color-text);
    font-weight: bold;
  }

  nav a.router-link-exact-active:hover {
    background-color: transparent;
  }

  @media (hover: hover) {
    nav a:hover {
      background-color: var(--color-background-soft);
    }
  }

  /* 登录/注册提示弹窗 */
  .auth-modal-mask {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.35);
    display: grid;
    place-items: center;
    z-index: 50;
  }

  .auth-modal {
    position: relative;
    width: min(360px, calc(100vw - 32px));
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #d7dee7;
    padding: 20px;
    text-align: center;
    box-shadow: 0 20px 45px rgba(15, 23, 42, 0.2);
  }

  /* 关闭按钮 */
  .modal-close {
    position: absolute;
    top: 8px;
    right: 10px;
    border: none;
    background: transparent;
    color: #475569;
    font-size: 20px;
    line-height: 1;
    cursor: pointer;
  }

  .modal-close:hover {
    background-color: #e81123;
    color: white;
  }

  /* 按钮组 */
  .modal-actions {
    margin-top: 12px;
    display: flex;
    justify-content: center;
    gap: 10px;
  }

  .action-btn {
    border-radius: 8px;
    background: #0f766e;
    color: #fff;
    padding: 8px 14px;
    text-decoration: none;
  }

  .action-btn.secondary {
    background: #475569;
  }

  /* 动画 */
  @keyframes spin {
    to {
      transform: rotate(360deg);
    }
  }

  /* 响应式调整 */
  @media (max-width: 1023px) {
    header {
      flex-direction: column;
      text-align: center;
    }

    .logo {
      margin: 0 auto;
    }

    .wrapper {
      flex-direction: column;
      width: 100%;
    }

    nav {
      width: 100%;
      margin-left: 0;
      justify-content: center;
    }
  }
</style>

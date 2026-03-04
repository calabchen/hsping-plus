<script setup lang="ts">
import { RouterLink, RouterView } from 'vue-router'
</script>

<template>
  <header>
    <img alt="Vue logo" class="logo" src="@/assets/logo.svg" width="125" height="125" />

    <div class="wrapper">
      <h1>毕设：评卷辅助系统</h1>

      <nav>
        <RouterLink to="/">首页</RouterLink>
        <RouterLink to="/dashboard">仪表盘</RouterLink>
        <RouterLink to="/login">登录</RouterLink>
        <RouterLink to="/register">注册</RouterLink>
        <RouterLink to="/about">关于</RouterLink>
      </nav>
    </div>
  </header>

  <RouterView />
</template>

<style scoped>
/* 旧的样式（已调整为新的上下结构） */
header {
  line-height: 1.5;
  /* max-height: 100vh; */ /* 改为 padding + border */
  padding: 1rem 2rem;
  border-bottom: 1px solid var(--color-border);
  display: flex;
  align-items: center;
  gap: 2rem;
}

.logo {
  /* display: block; */ /* 改为 flex-shrink */
  /* margin: 0 auto 2rem; */ /* 改为固定宽高 */
  flex-shrink: 0;
  width: 60px;
  height: 60px;
}

/* nav {
  width: 100%; */ /* 改为 display: flex */
  /* font-size: 12px; */ /* 改为 14px */
  /* text-align: center; */ /* 改为 margin-left: auto */
  /* margin-top: 2rem; */ /* 已移除 */
/* } */

nav {
  /* margin: 0; */
  font-size: 14px;
  margin-left: auto; /* 靠右对齐 */
  display: flex;
  /* gap: 0; */
}

.wrapper {
  display: flex;
  align-items: center;
  gap: 2rem;
  flex: 1;
}

.wrapper h1 {
  margin: 0;
  font-size: 1rem;
}

.wrapper h1:last-of-type {
  display: none;
}

nav a {
  display: inline-block;
  padding: 0.5rem 1rem;
  /* border-left: 1px solid var(--color-border); */ /* 改为 border-right */
  border-right: 1px solid var(--color-border);
}

nav a:last-of-type {
  /* border: 0; */ /* 改为 border-right: none */
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

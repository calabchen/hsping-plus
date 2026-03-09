<script setup lang="ts">
  import { reactive, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { authStore } from '@/stores/auth'

  const router = useRouter()
  const route = useRoute()

  const form = reactive({
    email: '',
    password: '',
  })

  const loading = ref(false)
  const errorMessage = ref('')

  const onSubmit = async () => {
    if (loading.value) {
      return
    }

    loading.value = true
    errorMessage.value = ''

    try {
      const normalizedEmail = form.email.trim().toLowerCase()
      await authStore.login(normalizedEmail, form.password)
      authStore.hideAuthRequiredModal()
      const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : '/dashboard'
      await router.push(redirect)
    } catch (error: any) {
      if (error.response?.data?.message) {
        errorMessage.value = error.response.data.message
      } else if (error.response?.data?.errors?.email?.[0]) {
        errorMessage.value = error.response.data.errors.email[0]
      } else {
        errorMessage.value = '登录失败，请检查账户和密码。'
      }
    } finally {
      loading.value = false
    }
  }
</script>

<template>
  <div class="auth-page">
    <form class="auth-card" @submit.prevent="onSubmit">
      <h1>登录</h1>

      <label class="field-label" for="email">账户（邮箱）</label>
      <input id="email" v-model="form.email" type="email" autocomplete="username" required maxlength="255" />
      <p class="hint-text">请输入有效邮箱格式，例如：teacher@example.com。</p>

      <label class="field-label" for="password">密码</label>
      <input id="password" v-model="form.password" type="password" autocomplete="current-password" required
        minlength="8" />
      <p class="hint-text">默认只要求至少 8 位，不强制大写、小写或特殊字符。</p>

      <p v-if="errorMessage" class="error-text">{{ errorMessage }}</p>

      <button type="submit" :disabled="loading">
        {{ loading ? '登录中...' : '登录' }}
      </button>
    </form>
  </div>
</template>

<style scoped>

  /* ────────────────────────────────────────────────
   1. 页面容器（整体居中 + 高度占满）
   ──────────────────────────────────────────────── */
  .auth-page {
    min-height: calc(100vh - 120px);
    display: grid;
    place-items: center;
    padding: 24px 16px;
  }

  /* ────────────────────────────────────────────────
   2. 卡片容器（最外层卡片）
   ──────────────────────────────────────────────── */
  .auth-card {
    width: min(420px, 100%);
    background: #ffffff;
    border: 1px solid #d7dee7;
    border-radius: 12px;
    padding: 22px;
    display: grid;
    gap: 10px;
    box-shadow: 0 10px 30px rgba(30, 41, 59, 0.06);
  }

  /* ────────────────────────────────────────────────
   3. 标题
   ──────────────────────────────────────────────── */
  .auth-card h1 {
    margin: 0 0 8px;
    font-size: 24px;
  }

  /* ────────────────────────────────────────────────
   4. 表单字段 - 标签 + 提示文字
   ──────────────────────────────────────────────── */
  .field-label {
    font-size: 14px;
    color: #334155;
  }

  .hint-text {
    margin: -4px 0 2px;
    font-size: 12px;
    color: #64748b;
  }

  /* ────────────────────────────────────────────────
   5. 输入框（共用样式）
   ──────────────────────────────────────────────── */
  input {
    height: 40px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 0 12px;
  }

  /* ────────────────────────────────────────────────
   6. 提交按钮状态
   ──────────────────────────────────────────────── */
  button {
    height: 40px;
    border: none;
    border-radius: 8px;
    background: #0f766e;
    color: #ffffff;
    font-weight: 600;
    cursor: pointer;
  }

  button:disabled {
    cursor: not-allowed;
    opacity: 0.7;
  }

  /* ────────────────────────────────────────────────
   7. 错误提示
   ──────────────────────────────────────────────── */
  .error-text {
    margin: 2px 0;
    color: #dc2626;
    font-size: 13px;
  }
</style>
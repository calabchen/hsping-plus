<script setup lang="ts">
  import { reactive, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import { authStore } from '@/stores/auth'
  import * as dhx from 'dhx-suite'
  import 'dhx-suite/codebase/suite.min.css'

  const router = useRouter()

  const form = reactive({
    name: '',
    email: '',
    password: '',
    passwordConfirmation: '',
  })

  const loading = ref(false)
  const errorMessage = ref('')

  const onSubmit = async () => {
    loading.value = true
    errorMessage.value = ''

    if (form.password !== form.passwordConfirmation) {
      errorMessage.value = '两次输入的密码不一致。'
      loading.value = false
      return
    }

    try {
      await authStore.register(form.name, form.email, form.password, form.passwordConfirmation)

      // 显示强制填写教师信息的确认框
      dhx.alert({
        header: '完善教师信息',
        text: '注册成功！为了更好地使用系统功能，请完善您的教师信息（姓名、性别、学科、学段等）。这些信息对于后续的班级管理和测验管理功能至关重要。',
        buttons: ['立即填写'],
        buttonsAlignment: 'center',
        css: 'teacher-info-confirm',
      }).then(() => {
        // 点击"立即填写"后直接跳转到个人资料页
        router.push('/dashboard/profile')
      })
    } catch (error: any) {
      // 显示后端返回的具体验证错误
      if (error.response?.data?.errors) {
        const errors = error.response.data.errors
        const firstErrorKey = Object.keys(errors)[0]
        errorMessage.value = firstErrorKey ? errors[firstErrorKey][0] : '注册失败，请检查输入信息。'
      } else if (error.response?.data?.message) {
        errorMessage.value = error.response.data.message
      } else {
        errorMessage.value = '注册失败，请检查输入信息。'
      }
    } finally {
      loading.value = false
    }
  }
</script>

<template>
  <div class="auth-page">
    <form class="auth-card" @submit.prevent="onSubmit">
      <h1>注册</h1>

      <label class="field-label" for="name">姓名</label>
      <input id="name" v-model="form.name" type="text" autocomplete="name" required maxlength="255" />
      <p class="hint-text">姓名必填，最多 255 个字符（中英文、数字、符号均可）。</p>

      <label class="field-label" for="email">邮箱</label>
      <input id="email" v-model="form.email" type="email" autocomplete="username" required maxlength="255" />
      <p class="hint-text">邮箱需为有效格式，例如：teacher@example.com。</p>

      <label class="field-label" for="password">密码</label>
      <input id="password" v-model="form.password" type="password" autocomplete="new-password" required minlength="8" />
      <p class="hint-text">默认只要求至少 8 位，不强制大写、小写或特殊字符。</p>

      <label class="field-label" for="passwordConfirmation">确认密码</label>
      <input id="passwordConfirmation" v-model="form.passwordConfirmation" type="password" autocomplete="new-password"
        required />

      <p v-if="errorMessage" class="error-text">{{ errorMessage }}</p>

      <button type="submit" :disabled="loading">
        {{ loading ? '注册中...' : '注册' }}
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

  /* ────────────────────────────────────────────────
   8. DHTMLX Message 自定义样式
   ──────────────────────────────────────────────── */
  :deep(.teacher-info-confirm) {
    --dhx-background-primary: #f0fdf4;
    --dhx-font-color-primary: #15803d;
    --dhx-font-color-secondary: #166534;
    --dhx-border-color: #86efac;
  }

  :deep(.teacher-info-confirm .dhx-message__header) {
    font-size: 18px;
    font-weight: 600;
    color: #15803d;
  }

  :deep(.teacher-info-confirm .dhx-message__text) {
    line-height: 1.6;
    color: #166534;
  }

  :deep(.teacher-info-confirm .dhx-button--primary) {
    background: #0f766e;
    border-color: #0f766e;
  }

  :deep(.teacher-info-confirm .dhx-button--primary:hover) {
    background: #0d9488;
    border-color: #0d9488;
  }
</style>
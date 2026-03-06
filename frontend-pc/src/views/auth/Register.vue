<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { authStore } from '@/stores/auth'

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
    await router.push('/dashboard')
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
      <input id="passwordConfirmation" v-model="form.passwordConfirmation" type="password" autocomplete="new-password" required />

      <p v-if="errorMessage" class="error-text">{{ errorMessage }}</p>

      <button type="submit" :disabled="loading">
        {{ loading ? '注册中...' : '注册' }}
      </button>
    </form>
  </div>
</template>

<style scoped>
.auth-page {
  min-height: calc(100vh - 120px);
  display: grid;
  place-items: center;
  padding: 24px 16px;
}

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

.auth-card h1 {
  margin: 0 0 8px;
  font-size: 24px;
}

.field-label {
  font-size: 14px;
  color: #334155;
}

.hint-text {
  margin: -4px 0 2px;
  font-size: 12px;
  color: #64748b;
}

input {
  height: 40px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  padding: 0 12px;
}

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

.error-text {
  margin: 2px 0;
  color: #dc2626;
  font-size: 13px;
}
</style>
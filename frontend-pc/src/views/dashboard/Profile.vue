<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { authStore } from '@/stores/auth'
import * as dhx from 'dhx-suite'
import { Layout as dhxLayout, Form as dhxForm } from 'dhx-suite'
import 'dhx-suite/codebase/suite.min.css'

const router = useRouter()
const layoutContainer = ref<HTMLElement>()

let layout: any = null
let teacherForm: any = null
let userForm: any = null

const errorMessage = ref<string>('')
const successMessage = ref<string>('')

onMounted(async () => {
  if (!authStore.isAuthenticated()) {
    router.push('/login')
    return
  }

  await api.get('/sanctum/csrf-cookie')

  if (layoutContainer.value) {
    layout = new dhxLayout(layoutContainer.value, {
      css: 'profile-layout',
      cols: [
        {
          id: 'user-section',
          width: '50%',
          css: 'user-cell'
        },
        {
          id: 'teacher-section',
          width: '50%',
          css: 'teacher-cell'
        }
      ]
    })

    // 创建 Users Form
    const userFormContainer = document.createElement('div')
    userForm = new dhxForm(userFormContainer, {
      css: 'dhx_widget--bordered',
      padding: 20,
      rows: [
        {
          type: 'text',
          label: 'users 账号信息',
          labelPosition: 'left',
          disabled: true,
          value: '',
          css: 'section-title'
        },
        {
          type: 'input',
          name: 'username',
          label: '用户名',
          labelPosition: 'top',
          placeholder: '请输入用户名',
          required: true
        },
        {
          type: 'input',
          name: 'email',
          label: '邮箱',
          labelPosition: 'top',
          inputType: 'email',
          placeholder: '请输入邮箱',
          required: true
        },
        {
          type: 'input',
          name: 'phone',
          label: '电话',
          labelPosition: 'top',
          inputType: 'tel',
          placeholder: '请输入电话'
        },
        {
          type: 'text',
          label: '修改密码（可选）',
          labelPosition: 'left',
          disabled: true,
          value: '',
          css: 'password-subtitle',
        },
        {
          type: 'input',
          name: 'current_password',
          label: '当前密码',
          labelPosition: 'top',
          inputType: 'password',
          placeholder: '请输入当前密码'
        },
        {
          type: 'input',
          name: 'new_password',
          label: '新密码',
          labelPosition: 'top',
          inputType: 'password',
          placeholder: '至少8位',
          minlength: 8
        },
        {
          type: 'input',
          name: 'new_password_confirmation',
          label: '确认新密码',
          labelPosition: 'top',
          inputType: 'password',
          placeholder: '再次输入新密码',
          minlength: 8
        },
        {
          cols: [
            {
              type: 'button',
              name: 'save_user',
              text: '应用新的账户信息',
              size: 'medium',
              view: 'flat',
              color: 'primary',
              css: 'save-button'
            },
            {
              type: 'button',
              name: 'delete_account',
              text: '注销账户',
              size: 'medium',
              view: 'flat',
              color: 'danger',
              css: 'delete-button'
            }
          ]
        }
      ]
    })

    // 创建 Teachers Form
    const teacherFormContainer = document.createElement('div')
    teacherForm = new dhxForm(teacherFormContainer, {
      css: 'dhx_widget--bordered',
      padding: 20,
      rows: [
        {
          type: 'text',
          label: 'teachers 教师信息',
          labelPosition: 'left',
          disabled: true,
          value: '',
          css: 'section-title'
        },
        {
          type: 'input',
          name: 'last_name',
          label: '姓',
          labelPosition: 'top',
          placeholder: '请输入姓'
        },
        {
          type: 'input',
          name: 'first_name',
          label: '名',
          labelPosition: 'top',
          placeholder: '请输入名'
        },
        {
          type: 'select',
          name: 'gender',
          label: '性别',
          labelPosition: 'top',
          options: [
            { value: '', content: '请选择' },
            { value: '男', content: '男' },
            { value: '女', content: '女' }
          ]
        },
        {
          type: 'input',
          name: 'subject',
          label: '学科',
          labelPosition: 'top',
          placeholder: '请输入学科'
        },
        {
          type: 'select',
          name: 'education_stage',
          label: '学段',
          labelPosition: 'top',
          options: [
            { value: '', content: '请选择' },
            { value: '小学', content: '小学' },
            { value: '初中', content: '初中' },
            { value: '高中', content: '高中' },
            { value: '大学', content: '大学' }
          ]
        },
        {
          type: 'button',
          name: 'save_teacher',
          text: '应用新的教师信息',
          size: 'medium',
          view: 'flat',
          color: 'primary',
          css: 'save-button'
        }
      ]
    })

    //把 Form 附加到 Layout 中
    layout.getCell('teacher-section').attach(teacherForm)
    layout.getCell('user-section').attach(userForm)

    // 加载数据
    await loadProfile()

    // 绑定事件
    teacherForm.events.on('click', (name: string) => {
      if (name === 'save_teacher') {
        saveTeacherInfo()
      }
    })

    userForm.events.on('click', (name: string) => {
      if (name === 'save_user') {
        saveUserInfo()
      } else if (name === 'delete_account') {
        deleteAccount()
      }
    })
  }
})

onBeforeUnmount(() => {
  if (teacherForm) {
    teacherForm.destructor()
  }
  if (userForm) {
    userForm.destructor()
  }
  if (layout) {
    layout.destructor()
  }
})

const loadProfile = async () => {
  try {
    const response = await api.get('/api/auth/profile')
    const users = response.data?.users || {}
    const teachers = response.data?.teachers || {}

    if (teacherForm) {
      teacherForm.setValue({
        last_name: teachers.last_name || '',
        first_name: teachers.first_name || '',
        gender: teachers.gender || '',
        subject: teachers.subject || '',
        education_stage: teachers.education_stage || ''
      })
    }

    if (userForm) {
      userForm.setValue({
        username: users.username || '',
        email: users.email || '',
        phone: users.phone || '',
        current_password: '',
        new_password: '',
        new_password_confirmation: ''
      })
    }
  } catch (error: any) {
    console.error('加载个人资料失败:', error)
    showError('加载个人资料失败: ' + (error.response?.data?.message || error.message))
  }
}

const saveUserInfo = async () => {
  if (!userForm) return

  const values = userForm.getValue()

  // 验证新密码
  if (values.new_password && values.new_password !== values.new_password_confirmation) {
    showError('新密码与确认密码不一致')
    return
  }

  try {
    const payload: Record<string, string> = {
      username: values.username,
      email: values.email,
      phone: values.phone
    }

    if (values.new_password) {
      if (!values.current_password) {
        showError('修改密码需要提供当前密码')
        return
      }

      payload.current_password = values.current_password
      payload.new_password = values.new_password
      payload.new_password_confirmation = values.new_password_confirmation
    }

    await api.put('/api/auth/profile/user', payload)
    
    // 清空密码字段
    userForm.setValue({
      current_password: '',
      new_password: '',
      new_password_confirmation: ''
    })

    showSuccess('账号信息已更新')
    await loadProfile()
  } catch (error: any) {
    if (error.response?.data?.errors) {
      const errors = error.response.data.errors
      const firstErrorKey = Object.keys(errors)[0]
      if (firstErrorKey && errors[firstErrorKey] && errors[firstErrorKey][0]) {
        showError(errors[firstErrorKey][0])
      } else {
        showError('更新失败，请检查输入')
      }
    } else {
      showError(error.response?.data?.message || '网络错误，请稍后重试')
    }
  }
}

const saveTeacherInfo = async () => {
  if (!teacherForm) return

  const values = teacherForm.getValue()

  try {
    await api.put('/api/auth/profile/teacher', {
      last_name: values.last_name,
      first_name: values.first_name,
      gender: values.gender,
      subject: values.subject,
      education_stage: values.education_stage
    })

    showSuccess('教师信息已更新')
    await loadProfile()
  } catch (error: any) {
    if (error.response?.data?.errors) {
      const errors = error.response.data.errors
      const firstErrorKey = Object.keys(errors)[0]
      if (firstErrorKey && errors[firstErrorKey] && errors[firstErrorKey][0]) {
        showError(errors[firstErrorKey][0])
      } else {
        showError('更新失败，请检查输入')
      }
    } else {
      showError(error.response?.data?.message || '网络错误，请稍后重试')
    }
  }
}

const deleteAccount = async () => {
  dhx.confirm({
    header: '确认注销账户',
    text: '注销账户将永久删除您的所有数据，包括教师信息、题目、测验、学生提交记录等。此操作不可恢复！确定要继续吗？',
    buttons: ['取消', '确认注销'],
    buttonsAlignment: 'center',
    css: 'delete-account-confirm',
  }).then((result) => {
    if (result) {
      performAccountDeletion()
    }
  })
}

const performAccountDeletion = async () => {
  try {
    await api.delete('/api/auth/account')

    // 账户已删除后，前端立即清空认证状态并回到未登录首页
    authStore.state.user = null
    authStore.hideAuthRequiredModal()
    await router.replace('/')
  } catch (error: any) {
    console.error('删除账户失败:', error)
    showError('删除账户失败: ' + (error.response?.data?.message || error.message))
  }
}

const showSuccess = (message: string) => {
  successMessage.value = message
  errorMessage.value = ''
  setTimeout(() => {
    successMessage.value = ''
  }, 3000)
}

const showError = (message: string) => {
  errorMessage.value = message
  successMessage.value = ''
}
</script>

<template>
  <div class="profile-view-wrapper">
    <div class="page-header">
      <h1>个人资料管理</h1>

      <!-- 消息提示 -->
      <div class="header-alerts">
        <div v-if="successMessage" class="alert alert-success">
          {{ successMessage }}
        </div>
        <div v-if="errorMessage" class="alert alert-error">
          {{ errorMessage }}
        </div>
      </div>
    </div>

    <!-- Layout 容器 -->
    <div ref="layoutContainer" class="layout-container"></div>
  </div>
</template>

<style scoped>
.profile-view-wrapper {
  width: 100%;
  height: 100%;
  flex: 1;
  display: flex;
  flex-direction: column;
  background-color: var(--color-background);
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 20px;
  background: var(--color-background-soft);
  border-bottom: 1px solid var(--color-border);
}

.page-header h1 {
  margin: 0;
  color: var(--color-heading);
  font-size: 24px;
}

.header-alerts {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 10px;
  margin-left: auto;
}

.alert {
  padding: 8px 12px;
  white-space: nowrap;
  border-radius: 6px;
  font-size: 14px;
}

.alert-success {
  background: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.alert-error {
  background: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

.layout-container {
  flex: 1;
  width: 100%;
  overflow: hidden;
}

/* ────────────────────────────────────────────────
   DHTMLX Layout 自定义样式
   ──────────────────────────────────────────────── */
:deep(.profile-layout) {
  background: var(--color-background);
}

:deep(.teacher-cell) {
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
  border-bottom: 2px solid #0ea5e9;
}

:deep(.user-cell) {
  background: linear-gradient(135deg, #fefce8 0%, #fef3c7 100%);
}

/* ────────────────────────────────────────────────
   DHTMLX Form 自定义样式
   ──────────────────────────────────────────────── */
:deep(.section-title .dhx_input) {
  font-size: 18px;
  font-weight: bold;
  color: #1e293b;
  background: transparent;
  border: none;
  padding-left: 0;
}

:deep(.section-subtitle .dhx_input) {
  font-size: 14px;
  font-weight: 600;
  color: #64748b;
  background: transparent;
  border: none;
  padding-left: 0;
  margin-top: 2px;
  margin-bottom: 0;
}

:deep(.section-subtitle + .dhx_form-group) {
  margin-top: 4px !important;
}

:deep(.password-subtitle .dhx_input) {
  background: transparent;
  border: none;
  padding-left: 0;
}

:deep(.save-button button) {
  background: #0f766e;
  border-color: #0f766e;
  font-weight: 500;
  padding: 10px 24px;
  border-radius: 6px;
  transition: all 0.2s;
}

:deep(.save-button button:hover) {
  background: #0d9488;
  border-color: #0d9488;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(15, 118, 110, 0.3);
}

:deep(.dhx_form-group__label) {
  font-weight: 500;
  color: #334155;
  text-align: right !important;
  justify-content: flex-end !important;
  width: 120px !important;
  min-width: 120px !important;
  padding-right: 12px;
  box-sizing: border-box;
}

:deep(.dhx_form-group__content) {
  flex: 1;
  min-width: 0;
}

:deep(.dhx_input) {
  border-radius: 4px;
  border: 1px solid #cbd5e1;
  transition: border-color 0.2s;
  width: 100%;
  box-sizing: border-box;
}

:deep(.dhx_input:focus) {
  border-color: #0f766e;
  box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);
}

:deep(.dhx_select) {
  border-radius: 4px;
  width: 100%;
  box-sizing: border-box;
}

/* ────────────────────────────────────────────────
   删除按钮样式
   ──────────────────────────────────────────────── */
:deep(.delete-button button) {
  background: #dc2626;
  border-color: #dc2626;
  font-weight: 500;
  padding: 10px 24px;
  border-radius: 6px;
  transition: all 0.2s;
  color: #ffffff;
}

:deep(.delete-button button:hover) {
  background: #b91c1c;
  border-color: #b91c1c;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
}

/* ────────────────────────────────────────────────
   删除确认对话框样式
   ──────────────────────────────────────────────── */
:deep(.delete-account-confirm) {
  --dhx-background-primary: #fef2f2;
  --dhx-font-color-primary: #991b1b;
  --dhx-font-color-secondary: #b91c1c;
  --dhx-border-color: #fca5a5;
}

:deep(.delete-account-confirm .dhx-message__header) {
  font-size: 18px;
  font-weight: 600;
  color: #991b1b;
}

:deep(.delete-account-confirm .dhx-message__text) {
  line-height: 1.6;
  color: #b91c1c;
}

:deep(.delete-account-confirm .dhx-button--primary) {
  background: #dc2626;
  border-color: #dc2626;
  color: #ffffff;
}

:deep(.delete-account-confirm .dhx-button--primary:hover) {
  background: #b91c1c;
  border-color: #b91c1c;
}
</style>

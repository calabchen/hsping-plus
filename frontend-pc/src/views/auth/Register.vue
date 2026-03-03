<script lang="ts">
import axios from 'axios';

axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;
axios.defaults.baseURL = 'http://localhost:8000';

const register = async () => {
  console.log('正在注册用户...');
  await axios.get('/sanctum/csrf-cookie', { withCredentials: true }); // 获取 CSRF token
  try {
    const response = await axios.post('/api/register', {
      name: 'John Doe',
      email: 'john@doe.com',
      password: 'password',
      password_confirmation: 'password',
    });
    console.log('注册成功:', response.data);
  } catch (error) {
    console.error('注册失败:', error);
  }
};

register();
</script>

<template>
  <div class="register">
    <h1>注册</h1>
  </div>
</template>
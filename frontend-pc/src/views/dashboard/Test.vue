<script setup lang="ts">
  import { useTestDashboardPage } from '@/components/test/useTestDashboardPage'

  const { layoutContainer, successMessage, errorMessage, isSheetLoading } = useTestDashboardPage()
</script>

<template>
  <div class="test-view-wrapper">
    <div class="page-header">
      <h1>测验管理</h1>
      <div class="header-alerts">
        <div v-if="successMessage" class="alert alert-success">{{ successMessage }}</div>
        <div v-if="errorMessage" class="alert alert-error">{{ errorMessage }}</div>
      </div>
    </div>

    <div class="layout-shell">
      <div ref="layoutContainer" class="layout-container"></div>
      <div v-if="isSheetLoading" class="sheet-loading-overlay sheet-loading-overlay--sheet-content">
        <div class="sheet-loading-spinner"></div>
        <span>正在加载题目数据...</span>
      </div>
    </div>
  </div>
</template>

<style scoped>

  /* Layout: 页面容器 */
  .test-view-wrapper {
    width: 100%;
    height: 100%;
    flex: 1;
    display: flex;
    flex-direction: column;
    background-color: var(--color-background);
  }

  /* Layout: 顶部标题栏 */
  .page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 20px;
    background: var(--color-background-soft);
    border-bottom: 1px solid var(--color-border);

    h1 {
      margin: 0;
      color: var(--color-heading);
      font-size: 24px;
    }
  }

  /* Layout: 右侧提示区 */
  .header-alerts {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 10px;
    margin-left: auto;
  }

  /* State: 成功/错误提示 */
  .alert {
    padding: 8px 12px;
    white-space: nowrap;
    border-radius: 6px;
    font-size: 14px;

    &.alert-success {
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    &.alert-error {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }
  }

  /* Layout: DHTMLX 挂载容器 */
  .layout-container {
    flex: 1;
    width: 100%;
    height: 100%;
    min-height: 0;
    overflow: hidden;
  }

  /* Layout: 遮罩定位壳 */
  .layout-shell {
    position: relative;
    display: flex;
    flex-direction: column;
    flex: 1;
    width: 100%;
    height: 100%;
    min-height: 0;
  }

  /* Overlay: 题目加载遮罩 */
  .sheet-loading-overlay {
    position: absolute;
    top: 0;
    bottom: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    background: rgba(255, 255, 255, 0.72);
    backdrop-filter: blur(1px);
    z-index: 20;
    color: #334155;
    font-size: 14px;

    &.sheet-loading-overlay--sheet-content {
      top: 56px;
      left: 40%;
      right: 0;
    }
  }

  /* Overlay: 加载动画 */
  .sheet-loading-spinner {
    width: 26px;
    height: 26px;
    border: 3px solid #cbd5e1;
    border-top-color: #0ea5e9;
    border-radius: 50%;
    animation: sheet-spin 0.8s linear infinite;
  }

  /* Overlay: 旋转关键帧 */
  @keyframes sheet-spin {
    from {
      transform: rotate(0deg);
    }

    to {
      transform: rotate(360deg);
    }
  }

  /* DHX: 两侧工具栏 */
  :deep(.app-test-toolbar),
  :deep(.app-sheet-toolbar) {
    border-bottom: 1px solid var(--color-border);
  }

  /* DHX: 状态单元格模板 */
  :deep(.dhx-demo_grid-template) {
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }

  /* DHX: 状态点颜色 */
  :deep(.dhx-demo_grid-status) {
    width: 8px;
    height: 8px;
    border-radius: 50%;
  }

  :deep(.dhx-demo_grid-status--done) {
    background-color: var(--dhx-color-success);
  }

  :deep(.dhx-demo_grid-status--in-progress) {
    background-color: #f59e0b;
  }

  :deep(.dhx-demo_grid-status--not-started) {
    background-color: var(--dhx-color-danger);
  }

  /* State: 行高亮 */
  :deep(.selected-test-row) {
    background: #dcfce7;
  }

  :deep(.selected-sheet-row) {
    background: #dcfce7;
  }
</style>

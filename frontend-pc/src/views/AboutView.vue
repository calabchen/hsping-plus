<template>
  <div class="about">
    <div class="about-container">
      <h2 class="project-title">
        毕业设计: 慧扫评——评卷辅助系统
      </h2>

      <h3 class="section-title">
        项目简介
      </h3>

      <p class="description-text">
        本项目旨在开发一个面向中小学日常教学测评的轻量化智能阅评系统。该系统能够基于手机、平板等移动端设备,利用OCR识别与大数据分析技术,实现答题卡的即时扫描与精准识别,自动生成包含平均分、正确率、知识点掌握分布、错题集中度等多维度学情数据的可视化报告。
      </p>

      <p class="description-text">
        系统支持移动端快速采集与PC端统一管理,构建“采集—分析—反馈—决策”全链路教学评价闭环,降低传统阅卷对专用设备的依赖,提升学情分析效率与教学决策精准度。
      </p>

      <p class="description-text">
        本系统致力于推动教学评价从“结果导向”向“过程驱动”转变,为中小学提供一套低成本、易操作、可推广的智能教学评价解决方案。
      </p>

      <p class="copyright-info">
        © 2025 慧扫评 版权所有
      </p>
      
      <h3 class="section-title">
        项目技术栈
      </h3>
      <p class="description-text tech-stack-text">
        <span class="tech-item">后端: PHP,Laravel</span>
        <span class="tech-item">前端: JavaScript, TypeScript, Vue3</span>
        <span class="tech-item">移动端: Uni-app</span>
        <span class="tech-item">渲染组件: DHTMLX</span>
      </p>
    </div>
    
    </div>
</template>

<style scoped>
.about {
  margin: 0;
  padding: 8px 0 24px;
}

.about-container {
  max-width: 960px;
  margin: 0 auto;
  width: 100%;
  padding: 0 20px;
  color: var(--color-text);
}

.project-title {
  margin: 0 0 16px;
  text-align: center;
  color: var(--color-heading);
  font-size: 28px;
  font-weight: 700;
  letter-spacing: 0.5px;
}

.section-title {
  margin: 0 0 14px;
  color: var(--color-heading);
  font-size: 20px;
  border-bottom: 2px solid var(--color-border-hover);
  padding-bottom: 8px;
  display: inline-block;
}

.description-text {
  margin: 0 0 14px;
  font-size: 16px;
  line-height: 1.85;
  text-indent: 2em;
}

.tech-stack-text {
  margin-bottom: 8px;
  text-indent: 0;
  padding: 10px 14px;
  border-left: 3px solid var(--color-border-hover);
  background: var(--color-background-soft);
  border-radius: 6px;
}

.tech-item {
  display: block;
  line-height: 1.8;
}

.copyright-info {
  margin: 26px 0 0;
  text-align: center;
  font-size: 14px;
  color: var(--color-text);
  opacity: 0.7;
}

@media (min-width: 1024px) {
  .about {
    padding-top: 12px;
  }
}
</style>

import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import ElementPlus from 'element-plus';
import 'element-plus/dist/index.css';
import * as ElementPlusIconsVue from '@element-plus/icons-vue';
import zhCn from 'element-plus/es/locale/lang/zh-cn';
import router from './router';
import App from './App.vue';

try {
    const app = createApp(App);
    const pinia = createPinia();

    // 注册所有图标
    for (const [key, component] of Object.entries(ElementPlusIconsVue)) {
        app.component(key, component);
    }

    app.use(pinia);
    app.use(router);
    app.use(ElementPlus, { locale: zhCn });
    
    // 确保 DOM 元素存在后再挂载
    const appElement = document.getElementById('app');
    if (appElement) {
        app.mount('#app');
    } else {
        console.error('找不到 #app 元素，无法挂载 Vue 应用');
    }
} catch (error) {
    console.error('Vue 应用初始化失败:', error);
}

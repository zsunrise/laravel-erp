<template>
    <div class="tabs-view">
        <div class="tabs-container" ref="tabsContainerRef">
            <div 
                v-for="tab in tabs" 
                :key="tab.path"
                :class="['tab-item', { 'is-active': tab.path === activeTab }]"
                @click="handleTabClick(tab)"
                @contextmenu.prevent="handleContextMenu($event, tab)"
            >
                <span class="tab-title">{{ tab.title }}</span>
                <span 
                    v-if="tabs.length > 1"
                    class="tab-close"
                    @click.stop="handleCloseTab(tab)"
                >
                    <X :size="12" />
                </span>
            </div>
        </div>
        <div class="tabs-actions">
            <el-dropdown @command="handleCommand" trigger="click">
                <el-button type="text" size="small" class="tabs-dropdown-btn">
                    <MoreHorizontal :size="16" />
                </el-button>
                <template #dropdown>
                    <el-dropdown-menu>
                        <el-dropdown-item command="closeOthers">关闭其他</el-dropdown-item>
                        <el-dropdown-item command="closeAll">关闭全部</el-dropdown-item>
                    </el-dropdown-menu>
                </template>
            </el-dropdown>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { X, MoreHorizontal } from 'lucide-vue-next';

const route = useRoute();
const router = useRouter();

const tabs = ref([]);
const tabsContainerRef = ref(null);

const activeTab = computed(() => route.path);

// 添加标签页
const addTab = (routeInfo) => {
    if (!routeInfo.meta || !routeInfo.meta.title) {
        return;
    }

    const tab = {
        path: routeInfo.path,
        name: routeInfo.name,
        title: routeInfo.meta.title,
        fullPath: routeInfo.fullPath || routeInfo.path
    };

    const existingTab = tabs.value.find(t => t.path === routeInfo.path);
    if (!existingTab) {
        tabs.value.push(tab);
    }
    
    // 滚动到当前标签
    nextTick(() => {
        scrollToActiveTab();
    });
};

// 移除标签页
const removeTab = (path) => {
    const index = tabs.value.findIndex(t => t.path === path);
    if (index > -1) {
        tabs.value.splice(index, 1);
    }
};

// 关闭标签页
const handleCloseTab = (tab) => {
    if (tabs.value.length === 1) {
        return; // 至少保留一个标签
    }

    const index = tabs.value.findIndex(t => t.path === tab.path);
    removeTab(tab.path);

    // 如果关闭的是当前标签，切换到相邻的标签
    if (tab.path === activeTab.value) {
        if (index > 0) {
            router.push(tabs.value[index - 1].path);
        } else if (tabs.value.length > 0) {
            router.push(tabs.value[0].path);
        }
    }
};

// 点击标签切换
const handleTabClick = (tab) => {
    if (tab.path !== activeTab.value) {
        router.push(tab.path);
    }
};

// 右键菜单
const handleContextMenu = (event, tab) => {
    // 可以在这里添加右键菜单功能
    // 例如：关闭其他、关闭右侧等
};

// 下拉菜单命令
const handleCommand = (command) => {
    if (command === 'closeOthers') {
        const currentTab = tabs.value.find(t => t.path === activeTab.value);
        tabs.value = currentTab ? [currentTab] : [];
    } else if (command === 'closeAll') {
        // 关闭全部后，跳转到首页
        tabs.value = [];
        router.push('/dashboard');
    }
};

// 滚动到活动标签
const scrollToActiveTab = () => {
    if (!tabsContainerRef.value) return;
    
    const activeElement = tabsContainerRef.value.querySelector('.tab-item.is-active');
    if (activeElement) {
        activeElement.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest',
            inline: 'center'
        });
    }
};

// 监听路由变化
watch(() => route.path, (newPath) => {
    if (route.meta && route.meta.title) {
        addTab({
            path: newPath,
            name: route.name,
            meta: route.meta,
            fullPath: route.fullPath
        });
    }
}, { immediate: true });

onMounted(() => {
    // 初始化时添加当前路由
    if (route.meta && route.meta.title) {
        addTab({
            path: route.path,
            name: route.name,
            meta: route.meta,
            fullPath: route.fullPath
        });
    }
});

// 暴露方法供外部调用
defineExpose({
    addTab,
    removeTab
});
</script>

<style scoped>
.tabs-view {
    display: flex;
    align-items: center;
    background-color: var(--color-bg);
    border-bottom: 1px solid var(--color-border);
    height: 40px;
    overflow: hidden;
}

.tabs-container {
    flex: 1;
    display: flex;
    align-items: center;
    overflow-x: auto;
    overflow-y: hidden;
    padding: 0 8px;
    gap: 4px;
    /* Firefox 滚动条样式 */
    scrollbar-width: thin;
    scrollbar-color: rgba(0, 0, 0, 0.1) transparent;
}

.tabs-container::-webkit-scrollbar {
    height: 4px;
}

.tabs-container::-webkit-scrollbar-track {
    background: transparent;
}

.tabs-container::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.1);
    border-radius: 2px;
}

.tabs-container::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 0, 0, 0.2);
}

.tab-item {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background-color: var(--color-bg-secondary);
    border: 1px solid var(--color-border);
    border-radius: var(--radius) var(--radius) 0 0;
    cursor: pointer;
    transition: var(--transition);
    white-space: nowrap;
    min-width: 80px;
    max-width: 200px;
    position: relative;
    user-select: none;
}

.tab-item:hover {
    background-color: var(--color-bg);
    border-color: var(--color-border-light);
}

.tab-item.is-active {
    background-color: var(--color-bg);
    border-bottom-color: var(--color-bg);
    color: var(--color-primary);
    font-weight: 500;
    border-color: var(--color-primary);
    border-bottom-color: transparent;
    z-index: 1;
}

.tab-title {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    font-size: 13px;
}

.tab-close {
    width: 14px;
    height: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: var(--transition);
    color: var(--color-text-secondary);
    flex-shrink: 0;
}

.tab-close:hover {
    background-color: var(--color-bg-secondary);
    color: var(--color-text-primary);
}

.tabs-actions {
    display: flex;
    align-items: center;
    padding: 0 8px;
    border-left: 1px solid var(--color-border);
    height: 100%;
}

.tabs-dropdown-btn {
    padding: 4px 8px;
    color: var(--color-text-secondary);
}

.tabs-dropdown-btn:hover {
    color: var(--color-primary);
    background-color: var(--color-bg-secondary);
}
</style>


import { ref, onBeforeUnmount } from 'vue';
import * as echarts from 'echarts';

// ECharts 5.x 会自动包含所有必要的组件，无需手动注册
// 如果仍有问题，可以考虑使用完整导入:
// import * as echarts from 'echarts/lib/echarts';

/**
 * ECharts 图表 composable
 */
export const useChart = (chartRef) => {
    const chartInstance = ref(null);

    const initChart = async (options = {}) => {
        if (!chartRef.value) {
            console.warn('Chart ref is not available');
            return false;
        }

        // 确保DOM元素有正确的尺寸和可见性
        const checkDomReady = () => {
            if (!chartRef.value) return false;
            const rect = chartRef.value.getBoundingClientRect();
            const computedStyle = window.getComputedStyle(chartRef.value);
            return rect.width > 0 && rect.height > 0 && computedStyle.display !== 'none' && computedStyle.visibility !== 'hidden';
        };

        // 等待DOM准备就绪，最多等待1秒
        let attempts = 0;
        while (!checkDomReady() && attempts < 100) {
            await new Promise(resolve => setTimeout(resolve, 10));
            attempts++;
        }

        if (!checkDomReady()) {
            console.warn('Chart DOM element is not ready or has no size, skipping initialization');
            return false;
        }

        try {
            if (!chartInstance.value) {
                chartInstance.value = echarts.init(chartRef.value, null, {
                    renderer: 'canvas',
                    useDirtyRect: false
                });
            }

            if (options && Object.keys(options).length > 0) {
                // 确保配置包含必要的坐标系
                const enhancedOptions = {
                    ...options,
                    // 确保坐标系配置正确
                    xAxis: options.xAxis || { type: 'category' },
                    yAxis: options.yAxis || { type: 'value' }
                };
                chartInstance.value.setOption(enhancedOptions, true);
            }
            return true;
        } catch (error) {
            console.error('Failed to initialize chart:', error);
            return false;
        }
    };

    const setOption = (option, notMerge = false) => {
        if (chartInstance.value) {
            try {
                chartInstance.value.setOption(option, notMerge);
                return true;
            } catch (error) {
                console.error('Failed to set chart option:', error);
                return false;
            }
        }
        return false;
    };

    const resize = () => {
        if (chartInstance.value) {
            try {
                chartInstance.value.resize();
                return true;
            } catch (error) {
                console.error('Failed to resize chart:', error);
                return false;
            }
        }
        return false;
    };

    const dispose = () => {
        if (chartInstance.value) {
            try {
                chartInstance.value.dispose();
                chartInstance.value = null;
            } catch (error) {
                console.error('Failed to dispose chart:', error);
            }
        }
    };

    const isInitialized = () => {
        return chartInstance.value !== null;
    };

    // 只在组件卸载时清理
    onBeforeUnmount(() => {
        dispose();
    });

    return {
        chartInstance,
        initChart,
        setOption,
        resize,
        dispose,
        isInitialized
    };
};


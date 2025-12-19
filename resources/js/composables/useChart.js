import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue';
import * as echarts from 'echarts';

/**
 * ECharts 图表 composable
 */
export const useChart = (chartRef, options = {}) => {
    const chartInstance = ref(null);

    const initChart = async () => {
        if (!chartRef.value) return;
        
        await nextTick();
        
        if (!chartInstance.value) {
            chartInstance.value = echarts.init(chartRef.value);
        }
        
        if (options) {
            chartInstance.value.setOption(options, true);
        }
    };

    const setOption = (option, notMerge = false) => {
        if (chartInstance.value) {
            chartInstance.value.setOption(option, notMerge);
        }
    };

    const resize = () => {
        if (chartInstance.value) {
            chartInstance.value.resize();
        }
    };

    const dispose = () => {
        if (chartInstance.value) {
            chartInstance.value.dispose();
            chartInstance.value = null;
        }
    };

    onMounted(() => {
        initChart();
        window.addEventListener('resize', resize);
    });

    onBeforeUnmount(() => {
        window.removeEventListener('resize', resize);
        dispose();
    });

    return {
        chartInstance,
        initChart,
        setOption,
        resize,
        dispose
    };
};


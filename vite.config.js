import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        cors: {
            origin: ['http://example-test.laravel.com', 'http://localhost:8000'],
            credentials: true
        },
        hmr: {
            // 宿主机 IP，虚拟机需要能访问到这个地址
            // 如果虚拟机通过 NAT 访问，使用宿主机在虚拟机网络中的 IP
            // 如果通过桥接，使用宿主机的实际 IP
            host: process.env.VITE_HMR_HOST || '192.168.31.115',
            port: 5173,
            protocol: 'ws'
        }
    }
});

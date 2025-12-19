<template>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h2>ERP管理系统</h2>
                <p>欢迎登录</p>
            </div>
            <el-form
                ref="loginFormRef"
                :model="loginForm"
                :rules="rules"
                class="login-form"
            >
                <el-form-item prop="email">
                    <el-input
                        v-model="loginForm.email"
                        placeholder="请输入邮箱"
                        size="large"
                        :prefix-icon="User"
                    />
                </el-form-item>
                <el-form-item prop="password">
                    <el-input
                        v-model="loginForm.password"
                        type="password"
                        placeholder="请输入密码"
                        size="large"
                        :prefix-icon="Lock"
                        @keyup.enter="handleLogin"
                    />
                </el-form-item>
                <el-form-item>
                    <el-button
                        type="primary"
                        size="large"
                        :loading="loading"
                        @click="handleLogin"
                        class="login-button"
                    >
                        登录
                    </el-button>
                </el-form-item>
            </el-form>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { ElMessage } from 'element-plus';
import { User, Lock } from '@element-plus/icons-vue';

const router = useRouter();
const authStore = useAuthStore();

const loginFormRef = ref(null);
const loading = ref(false);

const loginForm = reactive({
    email: '',
    password: ''
});

const rules = {
    email: [
        { required: true, message: '请输入邮箱', trigger: 'blur' },
        { type: 'email', message: '请输入正确的邮箱格式', trigger: 'blur' }
    ],
    password: [
        { required: true, message: '请输入密码', trigger: 'blur' },
        { min: 6, message: '密码长度不能少于6位', trigger: 'blur' }
    ]
};

const handleLogin = async () => {
    if (!loginFormRef.value) return;
    
    await loginFormRef.value.validate(async (valid) => {
        if (valid) {
            loading.value = true;
            try {
                await authStore.login(loginForm);
                ElMessage.success('登录成功');
                router.push('/dashboard');
            } catch (error) {
                ElMessage.error(error.response?.data?.message || '登录失败，请检查用户名和密码');
            } finally {
                loading.value = false;
            }
        }
    });
};
</script>

<style scoped>
.login-container {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.login-box {
    width: 400px;
    padding: 40px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
}

.login-header {
    text-align: center;
    margin-bottom: 30px;
}

.login-header h2 {
    margin-bottom: 10px;
    color: #303133;
}

.login-header p {
    color: #909399;
    font-size: 14px;
}

.login-form {
    margin-top: 20px;
}

.login-button {
    width: 100%;
}
</style>


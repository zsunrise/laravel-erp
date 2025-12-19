<template>
    <div class="profile-page">
        <el-card>
            <template #header>
                <div class="card-header">
                    <span>个人中心</span>
                </div>
            </template>

            <el-form
                ref="formRef"
                :model="form"
                :rules="rules"
                label-width="120px"
                style="max-width: 600px;"
            >
                <el-form-item label="头像">
                    <div class="avatar-upload">
                        <el-avatar :size="100" :src="form.avatar">
                            {{ form.name?.charAt(0) }}
                        </el-avatar>
                        <el-input
                            v-model="form.avatar"
                            placeholder="头像URL（可选）"
                            style="width: 300px; margin-left: 20px;"
                        />
                    </div>
                </el-form-item>

                <el-form-item label="姓名" prop="name">
                    <el-input v-model="form.name" />
                </el-form-item>

                <el-form-item label="邮箱" prop="email">
                    <el-input v-model="form.email" />
                </el-form-item>

                <el-form-item label="手机" prop="phone">
                    <el-input v-model="form.phone" />
                </el-form-item>

                <el-form-item label="角色">
                    <el-tag v-for="role in form.roles" :key="role.id" style="margin-right: 5px;">
                        {{ role.name }}
                    </el-tag>
                </el-form-item>

                <el-form-item label="状态">
                    <el-tag :type="form.is_active ? 'success' : 'danger'">
                        {{ form.is_active ? '启用' : '禁用' }}
                    </el-tag>
                </el-form-item>

                <el-form-item label="最后登录">
                    <span v-if="form.last_login_at">{{ formatDateTime(form.last_login_at) }}</span>
                    <span v-else>从未登录</span>
                </el-form-item>

                <el-divider>修改密码</el-divider>

                <el-form-item label="新密码" prop="password">
                    <el-input v-model="form.password" type="password" placeholder="留空则不修改密码" />
                </el-form-item>

                <el-form-item label="确认密码" prop="password_confirmation">
                    <el-input v-model="form.password_confirmation" type="password" placeholder="再次输入新密码" />
                </el-form-item>

                <el-form-item>
                    <el-button type="primary" @click="handleSubmit" :loading="submitLoading">保存修改</el-button>
                    <el-button @click="handleReset">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { ElMessage } from 'element-plus';
import api from '../services/api';
import { useAuthStore } from '../stores/auth';

const authStore = useAuthStore();
const formRef = ref(null);
const submitLoading = ref(false);

const form = reactive({
    id: null,
    name: '',
    email: '',
    phone: '',
    avatar: '',
    password: '',
    password_confirmation: '',
    roles: [],
    is_active: true,
    last_login_at: null
});

const validatePasswordConfirmation = (rule, value, callback) => {
    if (form.password && value != form.password) {
        callback(new Error('两次输入的密码不一致'));
    } else {
        callback();
    }
};

const rules = {
    name: [{ required: true, message: '请输入姓名', trigger: 'blur' }],
    email: [
        { required: true, message: '请输入邮箱', trigger: 'blur' },
        { type: 'email', message: '请输入正确的邮箱格式', trigger: 'blur' }
    ],
    phone: [{ pattern: /^1[3-9]\d{9}$/, message: '请输入正确的手机号码', trigger: 'blur' }],
    password: [{ min: 8, message: '密码长度不能少于8位', trigger: 'blur' }],
    password_confirmation: [{ validator: validatePasswordConfirmation, trigger: 'blur' }]
};

const formatDateTime = (dateTime) => {
    if (!dateTime) return '';
    const date = new Date(dateTime);
    return date.toLocaleString('zh-CN');
};

const loadUserInfo = async () => {
    try {
        const user = authStore.user;
        if (user) {
            Object.assign(form, {
                id: user.id,
                name: user.name || '',
                email: user.email || '',
                phone: user.phone || '',
                avatar: user.avatar || '',
                password: '',
                password_confirmation: '',
                roles: user.roles || [],
                is_active: user.is_active,
                last_login_at: user.last_login_at
            });
        } else {
            await authStore.fetchUser();
            const updatedUser = authStore.user;
            if (updatedUser) {
                Object.assign(form, {
                    id: updatedUser.id,
                    name: updatedUser.name || '',
                    email: updatedUser.email || '',
                    phone: updatedUser.phone || '',
                    avatar: updatedUser.avatar || '',
                    password: '',
                    password_confirmation: '',
                    roles: updatedUser.roles || [],
                    is_active: updatedUser.is_active,
                    last_login_at: updatedUser.last_login_at
                });
            }
        }
    } catch (error) {
        ElMessage.error('加载用户信息失败');
    }
};

const handleSubmit = async () => {
    try {
        await formRef.value.validate();
        
        submitLoading.value = true;
        const updateData = {
            name: form.name,
            email: form.email,
            phone: form.phone,
            avatar: form.avatar
        };

        if (form.password) {
            updateData.password = form.password;
        }

        await api.put(`/users/${form.id}`, updateData);
        
        await authStore.fetchUser();
        ElMessage.success('保存成功');
        
        form.password = '';
        form.password_confirmation = '';
    } catch (error) {
        if (error.response && error.response.data && error.response.data.message) {
            ElMessage.error(error.response.data.message);
        } else {
            ElMessage.error('保存失败');
        }
    } finally {
        submitLoading.value = false;
    }
};

const handleReset = () => {
    loadUserInfo();
};

onMounted(() => {
    loadUserInfo();
});
</script>

<style scoped>
.profile-page {
    padding: 20px;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.avatar-upload {
    display: flex;
    align-items: center;
    gap: 20px;
}

.avatar-uploader {
    margin-left: 10px;
}
</style>

import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import { authService } from '../services/authService';
import type { LoginRequest, RegisterRequest, User } from '../types';

export const useAuthStore = defineStore('auth', () => {
  const router = useRouter();
  const user = ref<User | null>(null);
  const token = ref<string | null>(localStorage.getItem('token'));

  const isAuthenticated = computed(() => !!token.value);
  const userRole = computed(() => user.value?.role);
  const isEmailVerified = computed(() => !!user.value?.email_verified_at);

  async function login(credentials: LoginRequest) {
    const response = await authService.login(credentials);

    if (response.success && response.data) {
      user.value = response.data.user;
      token.value = response.data.token;
      localStorage.setItem('token', response.data.token);
      localStorage.setItem('role', response.data.user.role);

      // Redirect berdasarkan role
      if (user.value.role === 'seller') {
        await router.push('/seller/dashboard');
      } else if (user.value.role === 'admin') {
        await router.push('/admin/dashboard');
      } else {
        await router.push('/');
      }
    }

    return response;
  }

  async function register(data: RegisterRequest) {
    const response = await authService.register(data);

    if (response.success && response.data) {
      user.value = response.data.user;
      // Tidak set token karena user belum verified
    }

    return response;
  }

  async function verifyEmail(email: string, code: string) {
    const response = await authService.verifyEmail({ email, code });

    if (response.success) {
      // Update user verification status if user data exists
      if (user.value && user.value.email === email) {
        user.value = {
          ...user.value,
          email_verified_at: new Date().toISOString(),
        };
      }
    }

    return response;
  }

  async function resendCode(email: string) {
    return await authService.resendVerificationCode(email);
  }

  async function logout() {
    if (token.value) {
      try {
        await authService.logout();
      } catch {
        // Ignore logout errors, still clear local state
      }
    }

    user.value = null;
    token.value = null;
    localStorage.removeItem('token');
    localStorage.removeItem('role');
    await router.push('/login');
  }

  async function fetchUser() {
    if (!token.value) return;

    try {
      const response = await authService.getCurrentUser();
      if (response.success && response.data) {
        user.value = response.data as User;
        localStorage.setItem('role', user.value.role);
      }
    } catch (error) {
      console.error('Failed to fetch user:', error);
      // Do not navigate from inside the session restore operation. The router
      // guard owns navigation and will send the user to login exactly once.
      user.value = null;
      token.value = null;
      localStorage.removeItem('token');
      localStorage.removeItem('role');
    }
  }

  return {
    user,
    token,
    isAuthenticated,
    userRole,
    isEmailVerified,
    login,
    register,
    verifyEmail,
    resendCode,
    logout,
    fetchUser,
  };
});

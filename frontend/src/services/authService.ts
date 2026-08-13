import type {
  ApiResponse,
  AuthResponse,
  EmailVerificationCode,
  LoginRequest,
  MessageResponse,
  RegisterRequest,
  User,
  VerifyEmailRequest
} from '../types';
import apiClient from './apiClient';

export const authService = {
  async login(data: LoginRequest): Promise<ApiResponse<AuthResponse>> {
    const response = await apiClient.post('/auth/login', data);
    return response.data;
  },

  async register(data: RegisterRequest): Promise<ApiResponse<AuthResponse>> {
    const response = await apiClient.post('/auth/register', data);
    return response.data;
  },

  async verifyEmail(data: VerifyEmailRequest): Promise<ApiResponse<MessageResponse>> {
    const response = await apiClient.post('/auth/verify-email', data);
    return response.data;
  },

  async resendVerificationCode(email: string): Promise<ApiResponse<EmailVerificationCode>> {
    const response = await apiClient.post('/auth/resend-code', { email });
    return response.data;
  },

  async logout(): Promise<ApiResponse<MessageResponse>> {
    const response = await apiClient.post('/auth/logout');
    return response.data;
  },

  async getCurrentUser(): Promise<ApiResponse<User>> {
    const response = await apiClient.get('/me');
    return response.data;
  },
};
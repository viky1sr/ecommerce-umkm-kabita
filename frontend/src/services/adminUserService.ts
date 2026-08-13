import type { PaginationMeta, User } from '../types';
import apiClient from './apiClient';

export const adminUserService = {
  async list(filters?: {
    role?: string;
    status?: string;
    search?: string;
    sort?: 'newest' | 'oldest';
    per_page?: number;
    page?: number;
  }): Promise<{ data: User[]; meta: PaginationMeta }> {
    const response = await apiClient.get('/users', { params: filters });
    return { data: response.data.data, meta: response.data.meta };
  },

  async suspend(userId: number): Promise<User> {
    const response = await apiClient.patch(`/users/${userId}/suspend`);
    return response.data.data;
  },

  async activate(userId: number): Promise<User> {
    const response = await apiClient.patch(`/users/${userId}/activate`);
    return response.data.data;
  },
};

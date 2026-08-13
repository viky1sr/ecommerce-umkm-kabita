import type { Category, CreateCategoryRequest, SingleResponse, UpdateCategoryRequest } from '../types';
import apiClient from './apiClient';

export const categoryService = {
  async list(): Promise<Category[]> {
    const response = await apiClient.get('/categories');
    return response.data.data ?? [];
  },

  async getBySlug(slug: string): Promise<SingleResponse<Category>> {
    const response = await apiClient.get<SingleResponse<Category>>(`/categories/${slug}`);
    return response.data;
  },

  async create(data: CreateCategoryRequest) {
    const response = await apiClient.post('/categories', data);
    return response.data;
  },

  async update(slug: string, data: UpdateCategoryRequest) {
    const response = await apiClient.put(`/categories/${slug}`, data);
    return response.data;
  },

  async delete(slug: string): Promise<void> {
    await apiClient.delete(`/categories/${slug}`);
  },
};

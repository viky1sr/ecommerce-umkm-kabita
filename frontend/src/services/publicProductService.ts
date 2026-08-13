import type { PaginatedResponse, PaginationMeta, Product, SingleResponse } from '../types';
import apiClient from './apiClient';

export const publicProductService = {
  async listAtHome(): Promise<PaginatedResponse<Product>> {
    const response = await apiClient.get<PaginatedResponse<Product>>('/public/products', {
      params: {
        sort: "newest",
        per_page: 8
      }
    });
    return response.data;
  },

  async list(filters?: {
    search?: string;
    category_id?: number;
    shop_id?: number;
    min_price?: number;
    max_price?: number;
    sort?: 'newest' | 'price_asc' | 'price_desc';
    per_page?: number;
    page?: number;
  }): Promise<{ data: Product[]; meta: PaginationMeta }> {
    const response = await apiClient.get('/public/products', { params: filters });
    return { data: response.data.data, meta: response.data.meta };
  },

  async getBySlug(slug: string): Promise<SingleResponse<Product>> {
    const response = await apiClient.get<SingleResponse<Product>>(`/public/products/${slug}`);
    return response.data;
  },
};

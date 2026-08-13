import type { CreateShopRequest, Shop, UpdateShopRequest } from '../types';
import apiClient from './apiClient';

export const sellerShopService = {
  async create(data: CreateShopRequest): Promise<Shop> {
    const formData = new FormData();
    formData.append('name', data.name);
    if (data.description !== undefined) {
      formData.append('description', data.description ?? '');
    }
    if (data.phone !== undefined) formData.append('phone', data.phone ?? '');
    if (data.address !== undefined) formData.append('address', data.address ?? '');
    if (data.logo) {
      formData.append('logo', data.logo);
    }
    if (data.banner) formData.append('banner', data.banner);

    const response = await apiClient.post('/shops', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    return response.data.data;
  },

  async getMyShop(): Promise<Shop> {
    const response = await apiClient.get('/shops/my-shop');
    return response.data.data;
  },

  async update(shopId: number, data: UpdateShopRequest): Promise<Shop> {
    const formData = new FormData();
    if (data.name !== undefined) {
      formData.append('name', data.name);
    }
    if (data.slug !== undefined) formData.append('slug', data.slug);
    if (data.description !== undefined) {
      formData.append('description', data.description ?? '');
    }
    if (data.phone !== undefined) formData.append('phone', data.phone ?? '');
    if (data.address !== undefined) formData.append('address', data.address ?? '');
    if (data.logo) {
      formData.append('logo', data.logo);
    }
    if (data.banner) formData.append('banner', data.banner);

    formData.append('_method', 'PUT');
    const response = await apiClient.post(`/shops/${shopId}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    return response.data.data;
  },

  async getBySlug(slug: string): Promise<Shop> {
    const response = await apiClient.get(`/shops/${slug}`);
    return response.data.data;
  },
};

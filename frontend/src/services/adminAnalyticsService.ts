import type {
  PlatformStats,
  SalesRow,
  TopProduct,
  TopSeller,
} from '../types';
import apiClient from './apiClient';

export const adminAnalyticsService = {
  async getPlatformStats(): Promise<PlatformStats> {
    const response = await apiClient.get('/analytics/platform');
    return response.data.data;
  },

  async getSales(period: 'daily' | 'weekly' | 'monthly' = 'monthly'): Promise<SalesRow[]> {
    const response = await apiClient.get('/analytics/sales', { params: { period } });
    return response.data.data;
  },

  async getTopSellers(perPage = 10): Promise<TopSeller[]> {
    const response = await apiClient.get('/analytics/top-sellers', { params: { per_page: perPage } });
    return response.data.data;
  },

  async getTopProducts(perPage = 10): Promise<TopProduct[]> {
    const response = await apiClient.get('/analytics/top-products', { params: { per_page: perPage } });
    return response.data.data;
  },

  async getCategoryRevenue(perPage = 10): Promise<Array<{ id: number; name: string; revenue: string }>> {
    const response = await apiClient.get('/analytics/category-revenue', { params: { per_page: perPage } });
    return response.data.data;
  },
};

import type { Order, PaginationMeta } from '../types'
import apiClient from './apiClient'

export const adminOrderService = {
  async list(filters?: { status?: string; search?: string; per_page?: number }): Promise<{ data: Order[]; meta: PaginationMeta }> {
    const response = await apiClient.get('/admin/orders', { params: filters })
    return { data: response.data.data, meta: response.data.meta }
  },
  async getDetail(id: number): Promise<Order> {
    const response = await apiClient.get(`/admin/orders/${id}`)
    return response.data.data
  },
}

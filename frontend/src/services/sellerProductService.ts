import type { PaginationMeta, Product } from '../types'
import apiClient from './apiClient'

export const sellerProductService = {
  async list(filters?: Record<string, string | number>): Promise<{ data: Product[]; meta: PaginationMeta }> {
    const response = await apiClient.get('/seller/products', { params: filters })
    return { data: response.data.data, meta: response.data.meta }
  },
  async getDetail(slug: string): Promise<Product> {
    const response = await apiClient.get(`/seller/products/${slug}`)
    return response.data.data
  },
  async create(data: Record<string, unknown> | FormData): Promise<Product> {
    const response = await apiClient.post('/seller/products', data, data instanceof FormData ? { headers: { 'Content-Type': 'multipart/form-data' } } : undefined)
    return response.data.data
  },
  async update(slug: string, data: Record<string, unknown> | FormData): Promise<Product> {
    const payload = data instanceof FormData ? data : { ...data, _method: 'PUT' }
    if (data instanceof FormData) data.append('_method', 'PUT')
    const response = await apiClient.post(`/seller/products/${slug}`, payload, data instanceof FormData ? { headers: { 'Content-Type': 'multipart/form-data' } } : undefined)
    return response.data.data
  },
  async remove(slug: string): Promise<void> { await apiClient.delete(`/seller/products/${slug}`) },
}

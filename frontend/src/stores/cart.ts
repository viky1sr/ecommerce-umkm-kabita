import { cartService } from '@/services/cartService'
import type { Cart, Product } from '@/types'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

// Price × qty → number
function calcSubtotal(price: number, quantity: number): number {
  return price * quantity
}

export const useCartStore = defineStore('cart', () => {
  const cart = ref<Cart | null>(null)
  const initialLoading = ref(false)
  const actionLoading = ref(false)
  const error = ref<string | null>(null)

  const flatItems = computed(() => cart.value?.groups_by_shop.flatMap((group) => group.items) ?? [])
  const totalItems = computed(() => flatItems.value.reduce((sum, i) => sum + i.quantity, 0))
  const totalPrice = computed(() => flatItems.value.reduce((sum, i) => sum + i.subtotal, 0))

  function syncFromCart(cartData: Cart) {
    cart.value = cartData
  }

  function normalizeCartPayload(raw: unknown): Cart | undefined {
    if (!raw || typeof raw !== 'object') return undefined
    const payload = raw as Record<string, unknown>
    if (Array.isArray(payload.groups_by_shop)) return raw as Cart
    const data = payload.data

    // Some endpoints return only { status, message } without data
    if (Array.isArray(data)) return (data as Cart[])[0]
    if (data && typeof data === 'object') return data as Cart
    return undefined
  }

  async function loadCart() {
    initialLoading.value = true
    error.value = null
    try {
      const data = await cartService.get()
      const cartData = normalizeCartPayload(data)
      if (cartData) syncFromCart(cartData)
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Gagal memuat keranjang'
    } finally {
      initialLoading.value = false
    }
  }

  async function addToCart(product: Product, quantity = 1) {
    error.value = null
    actionLoading.value = true
    try {
      await cartService.addItem({ product_id: product.id, quantity })
      await loadCart()
      return true
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Gagal menambahkan ke keranjang'
      return false
    } finally {
      actionLoading.value = false
    }
  }

  async function updateQuantity(cartItemId: number, quantity: number) {
    if (quantity < 1) return
    error.value = null
    const item = flatItems.value.find((i) => i.id === cartItemId)
    if (item && item.product) {
      const previousQuantity = item.quantity
      const previousSubtotal = item.subtotal
      // Update local cart first before hitting API
      item.quantity = quantity
      item.subtotal = calcSubtotal(item.product.price, quantity)
      try {
        await cartService.updateItem(cartItemId, { quantity })
        await loadCart()
      } catch (e) {
        error.value = e instanceof Error ? e.message : 'Gagal memperbarui kuantitas'
        item.quantity = previousQuantity
        item.subtotal = previousSubtotal
      }
    }
  }

  async function removeItem(cartItemId: number) {
    error.value = null
    const removed = flatItems.value.find((i) => i.id === cartItemId)
    if (cart.value) {
      cart.value.groups_by_shop.forEach((group) => {
        group.items = group.items.filter((i) => i.id !== cartItemId)
      })
    }
    try {
      await cartService.removeItem(cartItemId)
      await loadCart()
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Gagal menghapus item'
      if (removed && cart.value) {
        const group = cart.value.groups_by_shop.find((g) => g.items.some((i) => i.id === removed.id))
        if (group) group.items.push(removed)
      }
    }
  }

  async function clearCart() {
    error.value = null
    const previousCart = cart.value
    cart.value = null
    try {
      await cartService.clear()
      await loadCart()
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Gagal mengosongkan keranjang'
      cart.value = previousCart
    }
  }

  return {
    cart,
    flatItems,
    initialLoading,
    actionLoading,
    error,
    totalItems,
    totalPrice,
    loadCart,
    addToCart,
    updateQuantity,
    removeItem,
    clearCart,
  }
})

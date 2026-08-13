// =====================================================
// KABITA E-COMMERCE - API REQUEST / RESPONSE SCHEMAS
// Converted from: backend/api.json (components/schemas)
// =====================================================

// ─────────────────────────────────────────────────────
// AUTH REQUESTS
// ─────────────────────────────────────────────────────

export interface LoginRequest {
  email: string;
  password: string;
  remember?: boolean;
}

export interface RegisterRequest {
  name: string;
  email: string;
  phone: string;
  password: string;
  password_confirmation: string;
  role: 'buyer' | 'seller';
  shop_name?: string;
}

export interface VerifyEmailRequest {
  email: string;
  code: string;
}

export interface ResendVerificationCodeRequest {
  email: string;
}

// ─────────────────────────────────────────────────────
// CART REQUESTS
// ─────────────────────────────────────────────────────

export interface AddToCartRequest {
  product_id: number;
  quantity: number;
}

export interface UpdateCartItemRequest {
  quantity: number;
}

export interface CheckoutRequest {
  cart_items: number[];
  shipping_method: 'cod' | 'kurir';
  payment_method: 'transfer' | 'cod';
  shipping_address: string;
  cod_location?: string;
  notes?: string | null;
}

// ─────────────────────────────────────────────────────
// CATEGORY REQUESTS
// ─────────────────────────────────────────────────────

export interface CreateCategoryRequest {
  name: string;
  slug?: string;
  icon?: string | null;
  description?: string | null;
}

export interface UpdateCategoryRequest {
  name?: string;
  slug?: string;
  icon?: string | null;
  description?: string | null;
}

// ─────────────────────────────────────────────────────
// SHOP REQUESTS
// ─────────────────────────────────────────────────────

export interface CreateShopRequest {
  name: string;
  description?: string | null;
  phone?: string | null;
  address?: string | null;
  logo?: File | null;
  banner?: File | null;
}

export interface UpdateShopRequest {
  name?: string;
  slug?: string;
  description?: string | null;
  phone?: string | null;
  address?: string | null;
  logo?: File | null;
  banner?: File | null;
}

// ─────────────────────────────────────────────────────
// COD LOCATION REQUESTS
// ─────────────────────────────────────────────────────

export interface StoreLocationRequest {
  name: string;
  address: string;
  phone: string;
  latitude?: string | null;
  longitude?: string | null;
  is_default?: boolean | null;
}

// ─────────────────────────────────────────────────────
// PAYMENT REQUESTS
// ─────────────────────────────────────────────────────

export interface UploadPaymentRequest {
  proof_image: File;
}

export interface RejectPaymentRequest {
  rejection_reason: string | null;
}

// ─────────────────────────────────────────────────────
// PRODUCT REQUESTS
// ─────────────────────────────────────────────────────

export interface RejectProductRequest {
  rejection_reason: string;
}

export interface RejectShopRequest {
  rejection_reason: string;
}

export interface StoreProductInput {
  name: string;
  category_id: number | null;
  description?: string | null;
  price: number;
  cost_price: number | null;
  stock: number;
  weight: number | null;
  images: File[];
}

export interface StoreProductBulkRequest {
  products: StoreProductInput[];
}

export interface UpdateProductRequest {
  name?: string;
  category_id?: number | null;
  description?: string | null;
  price?: number;
  cost_price?: number | null;
  stock?: number;
  weight?: number | null;
  images?: File[];
}

// ─────────────────────────────────────────────────────
// ORDER REQUESTS
// ─────────────────────────────────────────────────────

export interface ShipOrderRequest {
  tracking_number?: string | null;
}

// ─────────────────────────────────────────────────────
// SHIPPING REQUESTS
// ─────────────────────────────────────────────────────

export interface ShippingCalculateRequest {
  weight: number;
  shipping_method: 'cod' | 'kurir';
  courier_type?: 'reguler' | 'express' | null;
}

// ─────────────────────────────────────────────────────
// USER REQUESTS (ADMIN)
// ─────────────────────────────────────────────────────

export interface SuspendUserRequest {
  reason?: string | null;
}

// ─────────────────────────────────────────────────────
// GENERIC RESPONSES
// ─────────────────────────────────────────────────────

export interface MessageResponse {
  success: boolean;
  message: string;
}

export interface ShippingOption {
  id: string;
  name: string;
  cost: number;
  base_cost: number;
  estimated_days: string | null;
}

export interface ShippingCalculation {
  shipping_method: string;
  courier_type: string;
  estimated_cost: number;
  estimated_days: string | null;
}

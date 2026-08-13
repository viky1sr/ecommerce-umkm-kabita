// =====================================================
// KABITA E-COMMERCE - DOMAIN ENTITY INTERFACES
// Converted from: backend/app/Models/ + backend/app/Http/Resources/
// =====================================================

import type {
  OrderStatus,
  PaymentStatus,
  ProductStatus,
  ShopStatus,
  UserRole,
  UserStatus,
} from './enums';

// ─────────────────────────────────────────────────────
// USER
// ─────────────────────────────────────────────────────

export interface User {
  id: number;
  name: string;
  email: string;
  email_verified_at: string | null;
  role: UserRole;
  phone: string | null;
  address: string | null;
  status: UserStatus;
  proof_image: string | null;
  verified_by: number | null;
  verified_at: string | null;
  created_at: string;
  updated_at: string;
  // Relations (when loaded via API)
  shop?: Shop;
  verifiedBy?: User;
}

// ─────────────────────────────────────────────────────
// SHOP
// ─────────────────────────────────────────────────────

export interface Shop {
  id: number;
  seller_id: number;
  name: string;
  slug: string;
  description: string | null;
  phone?: string | null;
  address?: string | null;
  logo: string | null;
  banner?: string | null;
  status: ShopStatus;
  verified_by: number | null;
  verified_at: string | null;
  rejection_reason: string | null;
  created_at: string;
  updated_at: string;
  // Relations (from ShopResource)
  seller?: User;
  verifier?: User;
  products?: Product[];
  product_count?: number;
}

// ─────────────────────────────────────────────────────
// CATEGORY
// ─────────────────────────────────────────────────────

export interface Category {
  id: number;
  name: string;
  slug: string;
  icon: string;
  description?: string | null;
  product_count: number | null;
  created_at: string;
  updated_at: string;
  // Relations (from CategoryResource)
  products?: Product[];
}

// ─────────────────────────────────────────────────────
// PRODUCT
// ─────────────────────────────────────────────────────

export interface Product {
  id: number;
  shop_id: number;
  category_id: number;
  name: string;
  slug: string;
  description: string | null;
  price: number;
  cost_price: number | null;
  stock: number;
  weight: number | null;
  status: ProductStatus;
  verified_at: string | null;
  rejection_reason: string | null;
  created_at: string;
  // Relations (from ProductResource)
  shop?: Shop;
  category?: Category;
  images?: ProductImage[];
}

// ─────────────────────────────────────────────────────
// PRODUCT IMAGE
// ─────────────────────────────────────────────────────

export interface ProductImage {
  id: number;
  url: string | null;
}

// ─────────────────────────────────────────────────────
// CART
// ─────────────────────────────────────────────────────

export interface Cart {
  id: number;
  buyer_id: number;
  groups_by_shop: CartGroupByShop[];
  subtotal: string;
  total: string;
  total_items: number;
  stock_status: CartStockStatus;
}

export interface CartGroupByShop {
  shop: CartShop;
  items: CartItem[];
  subtotal: string;
}

export interface CartShop {
  id: number;
  name: string;
  slug: string;
  logo: string | null;
}

export interface CartStockStatus {
  available: boolean;
  unavailable_items: CartUnavailableItem[];
}

export interface CartUnavailableItem {
  id: number;
  product_id: number;
  product_name: string;
  requested: number;
  available_stock: number;
}

export interface CartItem {
  id: number;
  product_id: number;
  quantity: number;
  product?: Product;
  subtotal: number;
}

// ─────────────────────────────────────────────────────
// Checkout ITEM
// ─────────────────────────────────────────────────────

export interface CheckoutItem {
  id: number;
  product_id: number;
  quantity: number;
  product?: Product;
  subtotal: number;
}

// ─────────────────────────────────────────────────────
// ORDER
// ─────────────────────────────────────────────────────

export interface Order {
  id: number;
  order_number: string;
  buyer_id: number;
  shop_id: number;
  subtotal: string;
  shipping_cost: string;
  total_amount: string;
  shipping_method: string;
  payment_method: string;
  status: OrderStatus;
  shipping_address: string;
  tracking_number: string | null;
  notes: string | null;
  created_at: string;
  updated_at: string;
  // Relations (from OrderResource)
  buyer?: User;
  shop?: Shop;
  items?: OrderItem[];
  payment?: Payment;
}

// ─────────────────────────────────────────────────────
// ORDER ITEM
// ─────────────────────────────────────────────────────

export interface OrderItem {
  id: number;
  order_id: number;
  product_id: number;
  quantity: number;
  price_snapshot: string;
  cost_snapshot: string;
  created_at: string;
  updated_at: string;
  // Relations (from OrderItemResource)
  product?: Product;
}

// ─────────────────────────────────────────────────────
// PAYMENT
// ─────────────────────────────────────────────────────

export interface Payment {
  id: number;
  order_id: number;
  amount: string;
  status: PaymentStatus;
  proof_image: string | null;
  created_at: string | null;
  updated_at: string | null;
  order?: {
    order_number: string;
    buyer?: User;
    shop?: Shop;
  };
}

// ─────────────────────────────────────────────────────
// ANALYTICS
// ─────────────────────────────────────────────────────

export interface AnalyticsSalesRow {
  date: string;
  total_orders: number;
  total_revenue: string;
  orders_count: number;
}

export interface PlatformStats {
  total_users: number;
  users_by_role: Record<string, number>;
  total_shops: number;
  shops_by_status: Record<string, number>;
  verified_shops: number;
  pending_shops: number;
  total_products: number;
  monthly_transactions: Array<{
    month: number;
    year: number;
    transactions: number;
    revenue: string;
  }>;
  platform_revenue: string;
}

export interface TopSeller {
  id: number;
  seller_id: number;
  shop_id: number;
  total_orders: number;
  total_revenue: string;
  shop?: Shop;
  seller?: User;
}

export interface TopProduct {
  id: number;
  shop_id: number;
  category_id: number;
  name: string;
  slug: string;
  price: string;
  cost_price: string;
  stock: number;
  weight: number | null;
  status: ProductStatus;
  total_sold: number;
  total_revenue: string;
  shop?: Shop;
  category?: Category;
}

// ─────────────────────────────────────────────────────
// SELLER ANALYTICS
// ─────────────────────────────────────────────────────

export interface SellerOverview {
  total_products: number;
  total_orders: number;
  total_revenue: string;
  pending_orders_count: number;
}

export interface SellerTopProduct {
  id: number;
  shop_id: number;
  category_id: number;
  name: string;
  slug: string;
  price: string;
  cost_price: string;
  stock: number;
  weight: number | null;
  status: ProductStatus;
  total_sold: number;
  total_revenue: string;
  profit: string;
  shop?: Shop;
  category?: Category;
}

export interface LowStockProduct {
  id: number;
  shop_id: number;
  category_id: number;
  name: string;
  slug: string;
  price: string;
  cost_price: string;
  stock: number;
  weight: number | null;
  status: ProductStatus;
  shop?: Shop;
  category?: Category;
}

// ─────────────────────────────────────────────────────
// COD LOCATION
// ─────────────────────────────────────────────────────

export interface CodLocation {
  id: number;
  name: string;
  address: string;
  phone: string;
  latitude: string | null;
  longitude: string | null;
  is_default: boolean;
  created_at?: string;
  updated_at?: string;
}

// ─────────────────────────────────────────────────────
// EMAIL VERIFICATION
// ─────────────────────────────────────────────────────

export interface EmailVerificationCode {
  verification_code: string;
}

// ─────────────────────────────────────────────────────
// DAILY PRODUCT SALES
// ─────────────────────────────────────────────────────

export interface DailyProductSales {
  id: number;
  date: string;
  product_id: number;
  shop_id: number;
  category_id: number;
  total_qty_sold: number;
  total_revenue: string;
  total_profit: string;
  created_at: string;
  updated_at: string;
}

// ─────────────────────────────────────────────────────
// DAILY PRODUCT SALES
// ─────────────────────────────────────────────────────

export interface DailyProductSales {
  id: number;
  date: string;
  product_id: number;
  shop_id: number;
  category_id: number;
  total_qty_sold: number;
  total_revenue: string;
  total_profit: string;
  created_at: string;
  updated_at: string;
}

// ─────────────────────────────────────────────────────
// EMAIL VERIFICATION CODE
// ─────────────────────────────────────────────────────

export interface EmailVerificationCode {
  id: number;
  user_id: number;
  code: string;
  expires_at: string;
  is_used: boolean;
  created_at: string;
  updated_at: string;
}

// ─────────────────────────────────────────────────────
// ANALYTICS ENTITIES (from api.json schemas)
// ─────────────────────────────────────────────────────

export interface PlatformStats {
  total_users: number;
  users_by_role: Record<string, number>;
  total_shops: number;
  shops_by_status: Record<string, number>;
  verified_shops: number;
  pending_shops: number;
  total_products: number;
  monthly_transactions: Array<{
    month: number;
    year: number;
    transactions: number;
    revenue: string;
  }>;
  platform_revenue: string;
}

export interface SellerOverview {
  total_products: number;
  total_orders: number;
  total_revenue: string;
  pending_orders_count: number;
}

export interface SalesRow {
  date: string;
  total_orders: number;
  total_revenue: string;
  orders_count: number;
}

export interface TopSeller {
  id: number;
  seller_id: number;
  shop_id: number;
  total_orders: number;
  total_revenue: string;
  shop?: Shop;
  seller?: User;
}

export interface TopProduct {
  id: number;
  shop_id: number;
  category_id: number;
  name: string;
  slug: string;
  price: string;
  cost_price: string;
  stock: number;
  weight: number | null;
  status: ProductStatus;
  total_sold: number;
  total_revenue: string;
  shop?: Shop;
  category?: Category;
}

// ─────────────────────────────────────────────────────
// SELLER ANALYTICS
// ─────────────────────────────────────────────────────

export interface SellerTopProduct {
  id: number;
  shop_id: number;
  category_id: number;
  name: string;
  slug: string;
  price: string;
  cost_price: string;
  stock: number;
  weight: number | null;
  status: ProductStatus;
  total_sold: number;
  total_revenue: string;
  profit: string;
  shop?: Shop;
  category?: Category;
}

export interface LowStockProduct {
  id: number;
  shop_id: number;
  category_id: number;
  name: string;
  slug: string;
  price: string;
  cost_price: string;
  stock: number;
  weight: number | null;
  status: ProductStatus;
  shop?: Shop;
  category?: Category;
}

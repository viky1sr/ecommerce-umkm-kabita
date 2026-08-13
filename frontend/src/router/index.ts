import { useAuthStore } from '@/stores/auth.ts';
import type { RouteRecordRaw } from 'vue-router';
import { createRouter, createWebHistory } from 'vue-router';

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'home',
    component: () => import('../views/HomeView.vue'),
    meta: { guest: true, title: 'Beranda - Kabita E-Commerce' }
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('../views/auth/LoginView.vue'),
    meta: { guest: true, title: 'Masuk - Kabita' }
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('../views/auth/RegisterView.vue'),
    meta: { guest: true, title: 'Daftar - Kabita' }
  },
  {
    path: '/verify-email',
    name: 'verify-email',
    component: () => import('../views/auth/EmailVerificationView.vue'),
    meta: { guest: true, title: 'Verifikasi Email - Kabita' }
  },
  {
    path: '/produk',
    name: 'product-list',
    component: () => import('../views/ProductListView.vue'),
    meta: { guest: true, title: 'Daftar Produk - Kabita' }
  },
  {
    path: '/produk/:id',
    name: 'product-detail',
    component: () => import('../views/ProductDetailView.vue'),
    meta: { guest: true, title: 'Detail Produk - Kabita' }
  },
  {
    path: '/tentang-kami',
    name: 'about',
    component: () => import('../views/BuyerInfoView.vue'),
    meta: { guest: true, title: 'Tentang Kabita' },
  },
  {
    path: '/bantuan',
    name: 'help',
    component: () => import('../views/BuyerInfoView.vue'),
    meta: { guest: true, title: 'Pusat Bantuan' },
  },
  {
    path: '/kategori/:slug',
    name: 'category',
    component: () => import('../views/CategoryView.vue'),
    meta: { guest: true, title: 'Kategori - Kabita' }
  },
  {
    path: '/profile/:slug?',
    name: 'profile',
    component: () => import('../views/buyer/profile/ProfileLayout.vue'),
    props: (route) => ({ slug: route.params.slug || 'account' }),
    meta: { requiresAuth: true, title: 'Profil Saya - Kabita' }
  },
  {
    path: '/checkout',
    name: 'checkout',
    component: () => import('../views/buyer/checkout/CheckoutView.vue'),
    meta: { requiresAuth: true, title: 'Checkout - Kabita' }
  },
  {
    path: '/cart',
    name: 'cart',
    component: () => import('../views/buyer/cart/CartView.vue'),
    meta: { requiresAuth: true, title: 'Keranjang Belanja - Kabita' }
  },
  {
    path: '/order-detail',
    name: 'order-detail',
    component: () => import('../views/buyer/order-detail/BuyerOrderDetailView.vue'),
    meta: { requiresAuth: true, title: 'Detail Pesanan - Kabita' }
  },

  // ==========================================
  // 1. ROUTE MODUL SELLER CENTER
  // ==========================================
  {
    path: '/seller',
    component: () => import('../views/seller/SellerLayout.vue'),
    meta: {
      requiresAuth: true,
      role: 'seller',
      hideHeaderFooter: true
    },
    children: [
      {
        path: '',
        redirect: '/seller/dashboard'
      },
      {
        path: 'dashboard',
        name: 'seller-dashboard',
        component: () => import('../views/seller/page/SellerDashboardView.vue'),
        meta: {
          requiresAuth: true,
          role: 'seller',
          hideHeaderFooter: true,
          title: 'Dashboard - Seller Center'
        }
      },
      {
        path: 'produk',
        name: 'seller-product-list',
        component: () => import('../views/seller/page/SellerProductListView.vue'),
        meta: {
          requiresAuth: true,
          role: 'seller',
          hideHeaderFooter: true,
          title: 'Kelola Produk - Seller Center'
        }
      },
      {
        path: 'produk/tambah',
        name: 'seller-product-create',
        component: () => import('../views/seller/page/SellerProductCreateView.vue'),
        meta: {
          requiresAuth: true,
          role: 'seller',
          hideHeaderFooter: true,
          title: 'Tambah Produk Baru - Seller Center'
        }
      },
      {
        path: 'produk/edit/:id',
        name: 'seller-product-edit',
        component: () => import('../views/seller/page/SellerProductEditView.vue'),
        props: true,
        meta: {
          requiresAuth: true,
          role: 'seller',
          hideHeaderFooter: true,
          title: 'Edit Produk - Seller Center'
        }
      },
      {
        path: 'pesanan',
        alias: 'orders',
        name: 'seller-order-list',
        component: () => import('../views/seller/page/SellerOrderListView.vue'),
        meta: {
          requiresAuth: true,
          role: 'seller',
          hideHeaderFooter: true,
          title: 'Kelola Pesanan - Seller Center'
        }
      },
      {
        path: 'pesanan/:id',
        alias: 'orders/:id',
        name: 'seller-order-detail',
        component: () => import('../views/seller/page/SellerOrderDetailView.vue'),
        props: true,
        meta: {
          requiresAuth: true,
          role: 'seller',
          hideHeaderFooter: true,
          title: 'Detail Pesanan - Seller Center'
        }
      },
      {
        path: 'profil-toko',
        name: 'seller-profile',
        component: () => import('../views/seller/page/SellerProfileView.vue'),
        meta: {
          requiresAuth: true,
          role: 'seller',
          hideHeaderFooter: true,
          title: 'Profil Toko - Seller Center'
        }
      },
      {
        path: 'analitik',
        name: 'seller-analytics',
        component: () => import('../views/seller/page/SellerAnalyticsView.vue'),
        meta: {
          requiresAuth: true,
          role: 'seller',
          hideHeaderFooter: true,
          title: 'Analitik Penjualan - Seller Center'
        }
      },
      {
        path: 'analitik/top-products',
        name: 'seller-analytics-top-products',
        component: () => import('../views/seller/page/SellerTopProductsView.vue'),
        meta: {
          requiresAuth: true,
          role: 'seller',
          hideHeaderFooter: true,
          title: 'Produk Terlaris - Seller Center'
        }
      },
      {
        path: 'pengaturan',
        name: 'seller-settings',
        component: () => import('../views/seller/page/SellerSettingsView.vue'),
        meta: {
          requiresAuth: true,
          role: 'seller',
          hideHeaderFooter: true,
          title: 'Pengaturan Toko - Seller Center'
        }
      },
      {
        path: ':slug?',
        name: 'seller-dynamic-slug',
        component: () => import('../views/seller/page/SellerDashboardView.vue'),
        props: (route) => ({ slug: route.params.slug || 'dashboard' }),
        meta: {
          requiresAuth: true,
          role: 'seller',
          hideHeaderFooter: true
        }
      }
    ]
  },

  // ==========================================
  // 2. ROUTE MODUL ADMIN INTERNAL
  // ==========================================
  {
    path: '/admin',
    component: () => import('../views/admin-internal/AdminInternalLayout.vue'),
    meta: {
      requiresAuth: true,
      role: 'admin',
      hideHeaderFooter: true
    },
    children: [
      // Redirect Otomatis /admin ke /admin/dashboard
      {
        path: '',
        redirect: '/admin/dashboard'
      },

      // Dashboard Utama Admin Internal
      {
        path: 'dashboard',
        name: 'admin-dashboard',
        component: () => import('../views/admin-internal/page/AdminDashboardView.vue'),
        meta: {
          requiresAuth: true,
          role: 'admin',
          hideHeaderFooter: true,
          title: 'Dashboard Admin - Kabita Internal'
        }
      },

      // Kelola Kategori Admin
      {
        path: 'kategori',
        name: 'admin-category',
        component: () => import('../views/admin-internal/page/AdminCategoryView.vue'),
        meta: {
          requiresAuth: true,
          role: 'admin',
          hideHeaderFooter: true,
          title: 'Kelola Kategori - Admin Center'
        }
      },
      {
        path: 'toko',
        name: 'admin-store-verification',
        component: () => import('../views/admin-internal/page/AdminStoreVerificationView.vue'),
        meta: { requiresAuth: true, role: 'admin', hideHeaderFooter: true, title: 'Verifikasi Toko - Admin Center' }
      },
      {
        path: 'user',
        name: 'admin-user-management',
        component: () => import('../views/admin-internal/page/AdminUserManagementView.vue'),
        meta: { requiresAuth: true, role: 'admin', hideHeaderFooter: true, title: 'Kelola User - Admin Center' }
      },
      {
        path: 'verifikasi',
        name: 'admin-product-verification',
        component: () => import('../views/admin-internal/page/AdminProductVerificationView.vue'),
        meta: { requiresAuth: true, role: 'admin', hideHeaderFooter: true, title: 'Verifikasi Produk - Admin Center' }
      },
      {
        path: 'transaksi',
        name: 'admin-order-management',
        component: () => import('../views/admin-internal/page/AdminOrderManagementView.vue'),
        meta: { requiresAuth: true, role: 'admin', hideHeaderFooter: true, title: 'Transaksi - Admin Center' }
      },
      {
        path: 'analitik',
        name: 'admin-analytics',
        component: () => import('../views/admin-internal/page/AdminAnalyticsView.vue'),
        meta: { requiresAuth: true, role: 'admin', hideHeaderFooter: true, title: 'Analitik Platform - Admin Center' }
      },
      {
        path: 'pengaturan',
        name: 'admin-settings',
        component: () => import('../views/admin-internal/page/AdminSettingsSystemSettingsView.vue'),
        meta: { requiresAuth: true, role: 'admin', hideHeaderFooter: true, title: 'Pengaturan - Admin Center' }
      },

      // Dynamic Slug Fallback untuk Sub-Menu Admin
      {
        path: ':slug?',
        name: 'admin-dynamic-slug',
        component: () => import('../views/admin-internal/page/AdminDashboardView.vue'),
        props: (route) => ({ slug: route.params.slug || 'dashboard' }),
        meta: {
          requiresAuth: true,
          role: 'admin',
          hideHeaderFooter: true,
          title: 'Admin Center - Kabita Internal'
        }
      }
    ]
  },

  // ==========================================
  // 3. FALLBACK WILDCARD ROUTE (404)
  // ==========================================
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('../views/not-found/NotFoundView.vue'),
    meta: { guest: true },
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 }
  }
})

// Navigation Guard dengan Hak Akses Admin, Seller, & Guest
router.beforeEach(async (to) => {
  const authStore = useAuthStore();

  // Restore the user before checking role-protected dashboard routes. A token
  // alone is not enough after a browser refresh because Pinia state is rebuilt.
  if (authStore.token && !authStore.user) {
    await authStore.fetchUser();
  }

  const token = authStore.token;
  const userRole = authStore.userRole || localStorage.getItem('role') || 'buyer';

  // Dynamic Document Title
  if (to.meta.title) {
    document.title = to.meta.title as string
  } else {
    document.title = 'Kabita E-Commerce'
  }

  // Verification & Access Guard
  if (to.meta.requiresAuth && !token) {
    return '/login'
  } else if (to.meta.role && to.meta.role !== userRole && token) {
    // Redirect pengguna jika mencoba mengakses halaman di luar kewenangannya (e.g. buyer -> admin)
    if (userRole === 'admin') return '/admin/dashboard'
    if (userRole === 'seller') return '/seller/dashboard'
    return '/'
  } else if (
    to.meta.guest &&
    token &&
    ['login', 'register', 'verify-email'].includes(String(to.name))
  ) {
    // Only authentication pages should redirect an already signed-in user.
    // Public catalog pages remain accessible after login.
    return '/'
  }

  return true
})

export default router;

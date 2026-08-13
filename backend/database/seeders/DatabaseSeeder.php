<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\ShopStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\CodLocation;
use App\Models\DailyProductSales;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentSetting;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->clearDemoData();

        $password = Hash::make('password');
        $admin = User::create([
            'name' => 'Administrator Kabita',
            'email' => 'admin@kabita.test',
            'password' => $password,
            'role' => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);

        $verifiedSeller = User::create([
            'name' => 'Sari Pemilik Batik',
            'email' => 'seller@kabita.test',
            'password' => $password,
            'role' => UserRole::SELLER,
            'status' => UserStatus::ACTIVE,
            'phone' => '081234567890',
            'email_verified_at' => now(),
            'verified_by' => $admin->id,
            'verified_at' => now(),
        ]);

        $pendingSeller = User::create([
            'name' => 'Budi Usaha Lokal',
            'email' => 'seller.pending@kabita.test',
            'password' => $password,
            'role' => UserRole::SELLER,
            'status' => UserStatus::INACTIVE,
            'phone' => '081298765432',
            'email_verified_at' => now(),
        ]);

        $buyer = User::create([
            'name' => 'Dewi Pembeli',
            'email' => 'buyer@kabita.test',
            'password' => $password,
            'role' => UserRole::BUYER,
            'status' => UserStatus::ACTIVE,
            'phone' => '082112223333',
            'address' => 'Jl. Merdeka No. 10, Bandung',
            'email_verified_at' => now(),
        ]);

        $categories = collect([
            ['name' => 'Makanan & Minuman', 'slug' => 'makanan-minuman'],
            ['name' => 'Fashion', 'slug' => 'fashion'],
            ['name' => 'Kerajinan', 'slug' => 'kerajinan'],
            ['name' => 'Kecantikan', 'slug' => 'kecantikan'],
        ])->mapWithKeys(fn (array $data) => [$data['slug'] => Category::create($data)]);

        $verifiedShop = Shop::create([
            'seller_id' => $verifiedSeller->id,
            'name' => 'Batik Kabita Sari',
            'slug' => 'batik-kabita-sari',
            'description' => 'Batik lokal dengan desain modern dan nyaman dipakai.',
            'status' => ShopStatus::VERIFIED,
            'verified_by' => $admin->id,
            'verified_at' => now(),
        ]);
        $pendingShop = Shop::create([
            'seller_id' => $pendingSeller->id,
            'name' => 'Dapur Budi UMKM',
            'slug' => 'dapur-budi-umkm',
            'description' => 'Produk makanan rumahan khas daerah.',
            'status' => ShopStatus::PENDING,
        ]);

        $products = collect([
            ['shop_id' => $verifiedShop->id, 'category_id' => $categories['fashion']->id, 'name' => 'Batik Parang Modern', 'slug' => 'batik-parang-modern', 'price' => 185000, 'cost_price' => 120000, 'stock' => 24, 'status' => ProductStatus::APPROVED, 'verified_at' => now()],
            ['shop_id' => $verifiedShop->id, 'category_id' => $categories['fashion']->id, 'name' => 'Scarf Batik Nusantara', 'slug' => 'scarf-batik-nusantara', 'price' => 75000, 'cost_price' => 42000, 'stock' => 40, 'status' => ProductStatus::APPROVED, 'verified_at' => now()],
            ['shop_id' => $verifiedShop->id, 'category_id' => $categories['kerajinan']->id, 'name' => 'Tas Anyaman Rotan', 'slug' => 'tas-anyaman-rotan', 'price' => 145000, 'cost_price' => 90000, 'stock' => 12, 'status' => ProductStatus::APPROVED, 'verified_at' => now()],
            ['shop_id' => $verifiedShop->id, 'category_id' => $categories['fashion']->id, 'name' => 'Kemeja Batik Pending', 'slug' => 'kemeja-batik-pending', 'price' => 210000, 'cost_price' => 140000, 'stock' => 15, 'status' => ProductStatus::PENDING],
            ['shop_id' => $pendingShop->id, 'category_id' => $categories['makanan-minuman']->id, 'name' => 'Keripik Pisang Budi', 'slug' => 'keripik-pisang-budi', 'price' => 28000, 'cost_price' => 15000, 'stock' => 80, 'status' => ProductStatus::PENDING],
            ['shop_id' => $verifiedShop->id, 'category_id' => $categories['kecantikan']->id, 'name' => 'Sabun Herbal Ditolak', 'slug' => 'sabun-herbal-ditolak', 'price' => 35000, 'cost_price' => 18000, 'stock' => 20, 'status' => ProductStatus::REJECTED, 'rejection_reason' => 'Dokumen komposisi belum lengkap.'],
        ])->map(function (array $data): Product {
            return Product::create(array_merge([
                'description' => 'Produk UMKM pilihan Kabita dengan kualitas terbaik.',
                'weight' => 500,
            ], $data));
        });

        $approvedProducts = $products->filter(
            fn (Product $product): bool => $product->status === ProductStatus::APPROVED
        )->values();
        $cart = Cart::create(['buyer_id' => $buyer->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $approvedProducts[0]->id, 'quantity' => 1]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $approvedProducts[1]->id, 'quantity' => 2]);

        CodLocation::create([
            'user_id' => $buyer->id,
            'name' => 'Rumah Dewi',
            'address' => 'Jl. Merdeka No. 10, Bandung',
            'latitude' => -6.9175,
            'longitude' => 107.6191,
            'is_default' => true,
        ]);

        $this->createOrder($buyer, $verifiedShop, $approvedProducts[0], OrderStatus::DELIVERED, 'transfer', 'verified');
        $this->createOrder($buyer, $verifiedShop, $approvedProducts[1], OrderStatus::PROCESSING, 'transfer', 'pending');
        $this->createOrder($buyer, $verifiedShop, $approvedProducts[2], OrderStatus::PENDING, 'cod', 'pending');

        PaymentSetting::create([
            'bank_name' => 'Bank Kabita',
            'account_number' => '1234567890',
            'account_holder_name' => 'PT Kabita Marketplace',
            'is_active' => true,
        ]);

        foreach ($approvedProducts as $product) {
            DailyProductSales::create([
                'date' => now()->subDay()->toDateString(),
                'product_id' => $product->id,
                'shop_id' => $product->shop_id,
                'category_id' => $product->category_id,
                'total_qty_sold' => 3,
                'total_revenue' => (float) $product->price * 3,
                'total_profit' => ((float) $product->price - (float) $product->cost_price) * 3,
            ]);
        }

        File::put(base_path('generated_users.txt'), implode("\n", [
            'Demo accounts (password: password)',
            'admin@kabita.test | admin',
            'seller@kabita.test | seller verified',
            'seller.pending@kabita.test | seller pending',
            'buyer@kabita.test | buyer',
        ]));

        $this->command?->info('Kabita demo data seeded: admin, sellers, buyer, shops, products, cart, orders, payments, COD, and analytics.');
    }

    private function createOrder(User $buyer, Shop $shop, Product $product, OrderStatus $status, string $paymentMethod, string $paymentStatus): Order
    {
        $subtotal = (float) $product->price;
        $shipping = $paymentMethod === 'cod' ? 0 : 10000;
        $order = Order::create([
            'order_number' => 'ORD-DEMO-' . strtoupper(substr(md5($product->slug . $status->value), 0, 8)),
            'buyer_id' => $buyer->id,
            'shop_id' => $shop->id,
            'subtotal' => $subtotal,
            'shipping_cost' => $shipping,
            'total_amount' => $subtotal + $shipping,
            'shipping_method' => $paymentMethod === 'cod' ? 'cod' : 'kurir',
            'payment_method' => $paymentMethod,
            'status' => $status,
            'shipping_address' => $buyer->address,
            'tracking_number' => $status === OrderStatus::SHIPPED || $status === OrderStatus::DELIVERED ? 'KABITA-DEMO-001' : null,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price_snapshot' => $product->price,
            'cost_snapshot' => $product->cost_price,
        ]);
        Payment::create([
            'order_id' => $order->id,
            'amount' => $order->total_amount,
            'status' => PaymentStatus::from($paymentStatus),
            'proof_image' => $paymentStatus === 'verified' ? 'payments/demo-proof.jpg' : null,
        ]);

        return $order;
    }

    private function clearDemoData(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach ([
            'daily_product_sales', 'payments', 'order_items', 'orders', 'cart_items', 'carts',
            'cod_locations', 'payment_settings', 'products', 'shops', 'categories', 'users',
        ] as $table) {
            DB::table($table)->delete();
        }
        Schema::enableForeignKeyConstraints();
    }
}

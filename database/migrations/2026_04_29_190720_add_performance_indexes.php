<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Products: homepage + listing query patterns ──
        Schema::table('products', function (Blueprint $table) {
            $table->index(['is_active', 'is_featured'], 'idx_products_active_featured');
            $table->index(['is_active', 'is_best_seller'], 'idx_products_active_bestseller');
            $table->index(['is_active', 'created_at'], 'idx_products_active_created');
            $table->index(['is_active', 'compare_price', 'price'], 'idx_products_active_sale');
            $table->index(['category_id', 'is_active'], 'idx_products_category_active');
            $table->index(['brand_id', 'is_active'], 'idx_products_brand_active');
        });

        // ── Product reviews: filtered aggregate queries ──
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->index(['product_id', 'is_approved'], 'idx_reviews_product_approved');
        });

        // ── Orders: dashboard stats ──
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['created_at', 'payment_status'], 'idx_orders_date_payment');
            $table->index('status', 'idx_orders_status');
        });

        // ── Cart items: lookup + deduplication ──
        Schema::table('cart_items', function (Blueprint $table) {
            $table->index(['cart_id', 'product_id', 'product_variant_id'], 'idx_cartitems_dedupe');
        });

        // ── Carts: session/user lookup ──
        Schema::table('carts', function (Blueprint $table) {
            $table->index('session_id', 'idx_carts_session');
            $table->index('user_id', 'idx_carts_user');
        });

        // ── Settings: key lookups ──
        Schema::table('settings', function (Blueprint $table) {
            $table->index('key', 'idx_settings_key');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_active_featured');
            $table->dropIndex('idx_products_active_bestseller');
            $table->dropIndex('idx_products_active_created');
            $table->dropIndex('idx_products_active_sale');
            $table->dropIndex('idx_products_category_active');
            $table->dropIndex('idx_products_brand_active');
        });

        Schema::table('product_reviews', function (Blueprint $table) {
            $table->dropIndex('idx_reviews_product_approved');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_date_payment');
            $table->dropIndex('idx_orders_status');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropIndex('idx_cartitems_dedupe');
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->dropIndex('idx_carts_session');
            $table->dropIndex('idx_carts_user');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropIndex('idx_settings_key');
        });
    }
};

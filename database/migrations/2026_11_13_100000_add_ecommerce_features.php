<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Harga coret: harga sebelum diskon (harus lebih besar dari `price`).
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('compare_at_price')->nullable()->after('price');
        });

        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'product_id']);
        });

        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20)->unique();
            $table->string('type', 10)->comment('percent|fixed');
            $table->unsignedInteger('value');
            $table->unsignedInteger('min_spend')->default(0);
            $table->unsignedInteger('max_discount')->nullable();
            $table->unsignedInteger('quota')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('voucher_code', 20)->nullable()->after('shipping_cost');
            $table->unsignedInteger('discount')->default(0)->after('voucher_code');
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['voucher_code', 'discount']));
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('wishlists');
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('compare_at_price'));
    }
};

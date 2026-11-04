<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $prefix = (string) config('promotions.table_prefix', 'promotion_');
        Schema::create($prefix.'promotions', function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->string('type', 20);
            $table->decimal('value', 12, 4);
            $table->decimal('minimum_order_amount', 12, 2)->default(0);
            $table->decimal('maximum_discount_amount', 12, 2)->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_limit_per_customer')->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->json('rules')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'starts_at', 'ends_at']);
        });

        Schema::create($prefix.'promotionables', function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->foreignId('promotion_id')->constrained($prefix.'promotions')->cascadeOnDelete();
            $table->morphs('promotionable');
            $table->unique(['promotion_id', 'promotionable_type', 'promotionable_id'], 'promotionables_unique');
        });

        Schema::create($prefix.'redemptions', function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->foreignId('promotion_id')->constrained($prefix.'promotions')->cascadeOnDelete();
            $table->nullableMorphs('customer');
            $table->string('order_reference');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_amount', 12, 2);
            $table->json('context')->nullable();
            $table->timestamp('redeemed_at');
            $table->timestamps();
            $table->unique(['promotion_id', 'order_reference'], 'promotion_redemption_order_unique');
            $table->index(['promotion_id', 'customer_type', 'customer_id'], 'promotion_redemption_customer_idx');
        });
    }

    public function down(): void
    {
        $prefix = (string) config('promotions.table_prefix', 'promotion_');
        Schema::dropIfExists($prefix.'redemptions');
        Schema::dropIfExists($prefix.'promotionables');
        Schema::dropIfExists($prefix.'promotions');
    }
};

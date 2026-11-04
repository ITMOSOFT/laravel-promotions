<?php

namespace ItmoSoft\Promotions\Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ItmoSoft\Promotions\Exceptions\PromotionException;
use ItmoSoft\Promotions\Models\Promotion;
use ItmoSoft\Promotions\PromotionService;
use ItmoSoft\Promotions\Tests\TestCase;

class PromotionServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'testing', 'database.connections.testing' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]);
        $this->app['db']->purge('testing');
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        Schema::create('users', function (Blueprint $table) { $table->id(); $table->timestamps(); });
    }

    public function test_percentage_discount_is_capped_and_rounded(): void
    {
        $promotion = Promotion::query()->create(['name' => 'Ten percent', 'type' => 'percentage', 'value' => 10, 'maximum_discount_amount' => 15, 'is_active' => true]);
        self::assertSame(15.0, app(PromotionService::class)->calculate($promotion, 200));
    }

    public function test_fixed_discount_cannot_exceed_subtotal(): void
    {
        $promotion = Promotion::query()->create(['name' => 'Fixed', 'type' => 'fixed', 'value' => 50, 'is_active' => true]);
        self::assertSame(20.0, app(PromotionService::class)->calculate($promotion, 20));
    }

    public function test_ineligible_context_is_rejected(): void
    {
        $promotion = Promotion::query()->create(['name' => 'Regional', 'type' => 'fixed', 'value' => 5, 'rules' => ['equals' => ['region' => 'PK']], 'is_active' => true]);
        $this->expectException(PromotionException::class);
        app(PromotionService::class)->calculate($promotion, 100, ['region' => 'US']);
    }

    public function test_redeem_records_a_redemption(): void
    {
        $promotion = Promotion::query()->create(['name' => 'Welcome', 'code' => 'WELCOME', 'type' => 'percentage', 'value' => 10, 'is_active' => true]);
        $redemption = app(PromotionService::class)->redeem($promotion, 'ORDER-100', 100);
        self::assertSame('10.00', $redemption->discount_amount);
        self::assertSame('ORDER-100', $redemption->order_reference);
    }

    public function test_global_usage_limit_is_enforced(): void
    {
        $promotion = Promotion::query()->create(['name' => 'Limited', 'type' => 'fixed', 'value' => 5, 'usage_limit' => 1, 'is_active' => true]);
        $service = app(PromotionService::class);
        $service->redeem($promotion, 'ORDER-1', 100);
        $this->expectException(PromotionException::class);
        $service->redeem($promotion, 'ORDER-2', 100);
    }
}

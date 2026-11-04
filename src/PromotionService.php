<?php

namespace ItmoSoft\Promotions;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use ItmoSoft\Promotions\Exceptions\PromotionException;
use ItmoSoft\Promotions\Models\Promotion;
use ItmoSoft\Promotions\Models\Redemption;

class PromotionService
{
    /** Calculate a discount without consuming a redemption. Amounts are in major currency units. */
    public function calculate(Promotion $promotion, float $subtotal, array $context = [], ?Model $customer = null, array $targets = [], ?DateTimeInterface $at = null): float
    {
        $this->assertEligible($promotion, $subtotal, $context, $customer, $targets, $at, false);
        return $this->discountFor($promotion, $subtotal);
    }

    /** Atomically validate usage limits and record a redemption. */
    public function redeem(Promotion|int|string $promotion, string $orderReference, float $subtotal, ?Model $customer = null, array $context = [], array $targets = [], ?DateTimeInterface $at = null): Redemption
    {
        if (trim($orderReference) === '') {
            throw new PromotionException('An order reference is required.');
        }
        if ($subtotal < 0) {
            throw new PromotionException('Subtotal cannot be negative.');
        }

        return DB::transaction(function () use ($promotion, $orderReference, $subtotal, $customer, $context, $targets, $at): Redemption {
            $class = config('promotions.models.promotion', Promotion::class);
            $id = $promotion instanceof Promotion ? $promotion->getKey() : $promotion;
            /** @var Promotion $locked */
            $locked = $class::query()->whereKey($id)->lockForUpdate()->firstOrFail();
            $this->assertEligible($locked, $subtotal, $context, $customer, $targets, $at, true);

            $redemptionClass = config('promotions.models.redemption', Redemption::class);
            return $redemptionClass::query()->create([
                'promotion_id' => $locked->getKey(),
                'customer_type' => $customer?->getMorphClass(),
                'customer_id' => $customer?->getKey(),
                'order_reference' => $orderReference,
                'subtotal' => round($subtotal, 2),
                'discount_amount' => $this->discountFor($locked, $subtotal),
                'context' => $context,
                'redeemed_at' => $at ?? now(),
            ]);
        });
    }

    private function assertEligible(Promotion $promotion, float $subtotal, array $context, ?Model $customer, array $targets, ?DateTimeInterface $at, bool $checkUsage): void
    {
        if ($subtotal < 0) throw new PromotionException('Subtotal cannot be negative.');
        if (!$promotion->isWithinSchedule($at)) throw new PromotionException('Promotion is inactive or outside its schedule.');
        if ($subtotal < (float) $promotion->minimum_order_amount) throw new PromotionException('Minimum order amount has not been met.');

        $rules = $promotion->rules ?? [];
        foreach (($rules['equals'] ?? []) as $key => $expected) {
            if (!array_key_exists($key, $context) || $context[$key] !== $expected) throw new PromotionException("Promotion is not eligible for context: {$key}.");
        }
        foreach (($rules['in'] ?? []) as $key => $allowed) {
            if (!is_array($allowed) || !in_array($context[$key] ?? null, $allowed, true)) throw new PromotionException("Promotion is not eligible for context: {$key}.");
        }

        $allowed = DB::table($promotion->tableName('promotionables'))
            ->where('promotion_id', $promotion->getKey())
            ->get(['promotionable_type', 'promotionable_id']);
        if ($allowed->isNotEmpty()) {
            $matches = collect($targets)->filter(fn ($target) => $target instanceof Model)->contains(
                fn (Model $target) => $allowed->contains(fn ($row) => $row->promotionable_type === $target->getMorphClass() && (string) $row->promotionable_id === (string) $target->getKey())
            );
            if (!$matches) throw new PromotionException('Promotion does not apply to the supplied items.');
        }

        if ($checkUsage) {
            $redemptions = $promotion->redemptions();
            if ($promotion->usage_limit !== null && $redemptions->count() >= (int) $promotion->usage_limit) throw new PromotionException('Promotion usage limit has been reached.');
            if ($customer && $promotion->usage_limit_per_customer !== null) {
                $used = $redemptions->where('customer_type', $customer->getMorphClass())->where('customer_id', $customer->getKey())->count();
                if ($used >= (int) $promotion->usage_limit_per_customer) throw new PromotionException('Customer usage limit has been reached.');
            }
        }
    }

    private function discountFor(Promotion $promotion, float $subtotal): float
    {
        $value = (float) $promotion->value;
        $discount = match ($promotion->type) {
            'percentage' => $subtotal * $value / 100,
            'fixed' => $value,
            default => throw new PromotionException('Unsupported promotion type.'),
        };
        if ($promotion->maximum_discount_amount !== null) $discount = min($discount, (float) $promotion->maximum_discount_amount);
        return round(min($subtotal, max(0, $discount)), 2);
    }
}

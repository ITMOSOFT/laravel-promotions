# ITMOSOFT Laravel Promotions

A reusable Laravel package for coupon codes and discount campaigns, with percentage/fixed discounts, schedules, eligibility rules, polymorphic item targeting, and redemption limits.

## Requirements

- PHP 8.3+
- Laravel 12 or 13

## Installation

```bash
composer require itmosoft/laravel-promotions
php artisan vendor:publish --tag=promotions-config
php artisan migrate
```

The package migrations load automatically. Set `PROMOTIONS_TABLE_PREFIX` before migrating if you need a custom prefix (default: `promotion_`).

## Create a promotion

```php
use ItmoSoft\Promotions\Models\Promotion;

$promotion = Promotion::query()->create([
    'name' => 'Welcome 10%',
    'code' => 'WELCOME10',
    'type' => 'percentage', // percentage | fixed
    'value' => 10,
    'minimum_order_amount' => 25,
    'maximum_discount_amount' => 50,
    'usage_limit' => 1000,
    'usage_limit_per_customer' => 1,
    'starts_at' => now(),
    'ends_at' => now()->addMonth(),
    'rules' => ['equals' => ['region' => 'PK'], 'in' => ['customer_tier' => ['gold', 'platinum']]],
    'is_active' => true,
]);
```

## Calculate and redeem

```php
use ItmoSoft\Promotions\PromotionService;

$service = app(PromotionService::class);
$discount = $service->calculate($promotion, 120.00, ['region' => 'PK']);

// Call only when the order is actually being committed. This writes a redemption.
$redemption = $service->redeem(
    promotion: $promotion,
    orderReference: 'ORDER-1001',
    subtotal: 120.00,
    customer: $user, // optional Eloquent model
    context: ['region' => 'PK', 'customer_tier' => 'gold'],
);
```

`redeem()` executes in a database transaction, locks the promotion row while checking usage caps, and records a unique `(promotion_id, order_reference)` redemption. Handle `PromotionException` as a validation failure. Database row locks depend on the database engine; SQLite does not provide production-equivalent row-level locking.

## Eligible products or other models

A promotion can be attached to arbitrary Eloquent models through the `promotionables` morph pivot. An empty target list means no item-level restriction. Pass the relevant models to `calculate()` or `redeem()`:

```php
DB::table('promotion_promotionables')->insert([
    'promotion_id' => $promotion->id,
    'promotionable_type' => (new Product())->getMorphClass(),
    'promotionable_id' => $product->id,
]);

$discount = $service->calculate($promotion, 120, [], $user, [$product]);
```

The table name above assumes the default prefix. Prefer your app's morph-map aliases consistently if using a morph map.

## Eligibility rules

- `rules.equals`: every context key must strictly equal its configured value.
- `rules.in`: every context key must be in its configured allowed list.
- `minimum_order_amount`: minimum eligible subtotal.
- `starts_at` / `ends_at`: inclusive schedule boundaries.
- `usage_limit`: total redemption cap.
- `usage_limit_per_customer`: cap per polymorphic customer.
- `maximum_discount_amount`: optional discount ceiling.

## Important boundaries

This package calculates and records promotion discounts only. It does not implement tax, currency conversion, cart/checkout, payment capture, stacking/combination policy, refunds, or order lifecycle integration. The caller is responsible for passing the correct eligible subtotal and invoking redemption at the correct point in its order transaction. Do not call `redeem()` merely to preview a discount.

## Tests

```bash
composer install
vendor/bin/phpunit
```

## License

MIT. See [LICENSE](LICENSE).

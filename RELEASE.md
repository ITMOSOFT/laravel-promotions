# Release checklist — v1.0.0

## GitHub repository

- Repository: `ITMOSOFT/laravel-promotions`
- Description: `A reusable Laravel promotions package with coupons, discount campaigns, eligibility rules, and usage limits. Supports Laravel 12 and 13.`
- Visibility: Public

## Verify locally

```bash
unzip itmosoft-laravel-promotions-v1.0.0.zip
cd itmosoft-laravel-promotions
composer install
vendor/bin/phpunit
```

## Commit and push

```bash
git init
git add .
git commit -m "feat: initial Laravel promotions package"
git branch -M main
git remote add origin https://github.com/ITMOSOFT/laravel-promotions.git
git push -u origin main
```

## Tag and release

```bash
git tag -a v1.0.0 -m "Initial stable release"
git push origin v1.0.0
```

GitHub Release title: `v1.0.0 — Initial Stable Release`

Release description:

```markdown
## What's included
- Percentage and fixed-amount promotions
- Coupon codes and scheduled campaigns
- Minimum order amount and maximum discount cap
- Context-based eligibility rules
- Polymorphic item targeting for any Eloquent model
- Global and per-customer redemption limits
- Transactional redemption with promotion row locking
- Configurable table prefix and model classes
- Laravel 12 and 13 compatibility CI

## Requirements
PHP 8.3+, Laravel 12 or 13.

## Installation
composer require itmosoft/laravel-promotions
```

## Packagist

1. Sign in to Packagist and submit `https://github.com/ITMOSOFT/laravel-promotions` as `itmosoft/laravel-promotions`.
2. Configure the Packagist GitHub service hook/webhook for the repository so new tags trigger updates.
3. Confirm `composer require itmosoft/laravel-promotions` resolves from Packagist after indexing.
4. For later releases, update `CHANGELOG.md`, push a semantic version tag, and let the webhook notify Packagist.

Do not claim the package is published until Packagist shows the package and a clean consumer install succeeds.

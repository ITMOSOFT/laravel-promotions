# Changelog

## 1.0.2 - 2026-11-25

- Require persisted customer models when a per-customer redemption limit is configured.
- Reject unsaved customer models passed to redemption.
- Normalize order references by trimming surrounding whitespace.
- Reject duplicate promotion/order redemption attempts with a package-level `PromotionException`.
- Reject unsaved promotion models during calculation or redemption.
- Add regression tests for customer limits and duplicate order references.

## 1.0.1 - 2026-11-04

- Fixed configurable table-prefix migration closures.

## 1.0.0 - 2026-11-04

- Initial stable release.
- Added percentage and fixed discounts, schedules, minimum subtotal and maximum discount controls.
- Added context-based eligibility, polymorphic item targeting, and redemption records.
- Added global and per-customer usage limits with transactional redemption.
- Added Laravel 12 and 13 CI matrix.

# Preorder Dates for WooCommerce

Sell on pre-order with two dates: when orders close and when the release ships.

Most free WooCommerce pre-order plugins track a single "available on" date. This one adds a
second, independent cutoff date, so you can say "we take orders until the 20th, and it ships on
the 30th" instead of only "it ships on the 30th".

## What it does

- Per-product and per-variation pre-order toggle, with a cutoff and a release date/time, in your
  store's timezone.
- Purchasable before the cutoff regardless of normal stock rules; blocked after the cutoff, on
  the product page, classic add-to-cart, and the Store API used by the cart/checkout blocks.
- Automatic cleanup after the release date (checked when the product is read, and once a day by
  WP-Cron as a backstop).
- "Pre-order" label and button on the product page and shop listings; "Pre-orders closed" once
  the cutoff passes.
- Release date shown in the cart, checkout (including the cart/checkout blocks) and on the
  order, so it appears in the order admin screen and in WooCommerce's order emails.
- Settings under WooCommerce > Settings > Products > Pre-order dates.
- Declared compatible with HPOS and with the cart/checkout blocks.

See `readme.txt` for the full WordPress.org listing, including the FAQ.

## Requirements

- PHP 8.1+
- WordPress 6.6+
- WooCommerce 9.0+

## Development

No build step: plain PHP, no Composer, no JS bundling. Run `php -l` on changed files before
committing.

## License

GPLv2 or later. See `LICENSE`.

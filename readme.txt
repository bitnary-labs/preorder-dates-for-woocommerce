=== Preorder Dates for WooCommerce ===
Contributors: dvsouto
Tags: pre-order, preorder, woocommerce, release date
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sell on pre-order with two dates: when orders close and when the release ships.

== Description ==

Preorder Dates for WooCommerce adds a pre-order mode to simple products and to individual
product variations, with two independent dates: the cutoff (when the store stops accepting
orders) and the release (when the product ships or becomes available).

Most free pre-order plugins only track one date, usually "available on". That is enough if you
want to sell an item before it exists in stock, but it does not let you say "we take orders
until the 20th, and it ships on the 30th", which is how pre-orders actually work for a lot of
small stores: a batch cutoff followed by a shared release.

While a product is in pre-order and before its cutoff, it is purchasable even if your normal
stock settings would otherwise say no (out of stock, zero quantity, no backorders). After the
cutoff, the product stops being purchasable everywhere WooCommerce checks that, including the
classic cart and the cart and checkout blocks (Store API). After the release date, pre-order is
cleared automatically and the product goes back to behaving like any other product.

What the free version does:

* Per-product and per-variation pre-order toggle, with a cutoff date/time and a release
  date/time, in your store's timezone.
* Purchasable before the cutoff regardless of normal stock rules; blocked after the cutoff, on
  the product page, classic add-to-cart, and the Store API used by the cart/checkout blocks.
* Automatic cleanup after the release date (checked when the product is read, and once a day by
  WP-Cron as a backstop).
* "Pre-order" label on the product page and in shop listings, with the ship and cutoff dates;
  "Pre-order" button text; "Pre-orders closed" message once the cutoff passes.
* Release date shown as a line item in the cart and checkout, including the cart/checkout
  blocks, and saved on the order so it shows up in the order admin screen and in WooCommerce's
  order emails.
* Editable text and date format under WooCommerce > Settings > Products > Pre-order dates.
* Declared compatible with High-Performance Order Storage (HPOS) and with the cart/checkout
  blocks.

What the free version does not do (see the FAQ): it does not block or split a cart that mixes
pre-order and in-stock items, it does not send a separate pre-order confirmation email or a
release countdown, and it has no bulk edit across products.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/preorder-dates-for-woocommerce`, or install
   it through the WordPress plugins screen.
2. Activate the plugin. WooCommerce must already be installed and active.
3. Edit a simple product and open the "Pre-order" tab in the Product data box, or edit a
   variation and open the pre-order fields inside the Variations tab.
4. Turn pre-order on, set when orders close and when the release ships.
5. Optionally adjust the label, button text, closed message and date format under
   WooCommerce > Settings > Products > Pre-order dates.

== Frequently Asked Questions ==

= Why two dates instead of one? =

One date only answers "when is it available". Two dates answer the two questions a shop
actually needs during a pre-order window: when do we stop taking orders for this batch, and when
does it ship. If your cutoff and release are the same day, just set them to the same value; the
plugin does not require them to differ.

= What happens exactly at the cutoff? =

The product stops being purchasable: `woocommerce_is_purchasable` and
`woocommerce_variation_is_purchasable` return false, the classic add-to-cart form and AJAX
add-to-cart are blocked, and adding the item through the Store API (used by the cart and
checkout blocks) is rejected with the message configured as "Closed message". The product page
and shop listings show that message in place of the pre-order label.

= What happens at the release date? =

Pre-order is cleared: the enable flag, cutoff and release dates are removed from the product or
variation, and it goes back to being handled entirely by WooCommerce's normal stock and
purchasability rules. This happens automatically the next time the product is read (viewed,
loaded in the admin, queried) or, at the latest, the next time the daily cleanup runs.

= What does the free version not do? =

It does not stop a customer from adding a pre-order item and an in-stock item to the same cart,
it does not send a dedicated pre-order confirmation email or a countdown to release, and it has
no way to edit pre-order dates on more than one product at a time. All three are part of the
paid version.

= Does this work with High-Performance Order Storage (HPOS) and the cart/checkout blocks? =

Yes. Both are declared compatible on `before_woocommerce_init` via
`FeaturesUtil::declare_compatibility()`, and the plugin only ever reads and writes product and
order data through WooCommerce's own CRUD methods, never raw SQL.

= Does this send emails or texts to customers automatically? =

No. The release date is added as a line on the cart, checkout, and order emails that WooCommerce
already sends; the plugin does not add its own separate emails or SMS.

= Where do I set the store timezone used for the dates? =

Settings > General > Timezone in WordPress. The plugin reads and writes all dates using that
timezone (`wp_timezone()`).

== Screenshots ==

1. Pre-order tab on a simple product, with the cutoff and release fields.
2. Pre-order fields on a variation, inside the Variations tab.
3. Pre-order label, release date and Pre-order button on the product page.
4. Block-based cart showing the Ships on date under a pre-order item.
5. The closed message on a product whose cutoff has passed.
6. Settings under WooCommerce > Settings > Products > Pre-order dates.
7. The Pre-orders screen under the WooCommerce menu.

== Changelog ==

= 0.2.0 =
* New: a "Pre-orders" screen under the WooCommerce menu, listing every product
  and variation taking orders or waiting for its release, ordered by whichever
  date comes next.
* New: the settings screen lists what the paid version adds. Two of those lines
  were reworded to match what it will actually do.
* Dev: unit tests and a `pdw_loaded` action, so the paid add-on has a stable
  place to hook into.

= 0.1.2 =
* Fix: the two nonce values read on save are now sanitized before they are verified.
* Change: dropped the manual translation loader. WordPress.org loads translations on its own
  since WordPress 4.6, so the call was doing nothing.

= 0.1.1 =
* Fix: a variable product's variations did not show the pre-order label/closed message or
  swap the add-to-cart button text when selected; the variation dropdown now carries its own
  availability text and button text, applied by a small enqueued script.
* Fix: a variation past its order cutoff showed the generic "Out of stock" text instead of the
  configured closed message.
* Fix: a variable product showed no pre-order indication in shop listings; it now shows the
  label of whichever variation is open for pre-order and ships soonest.

= 0.1.0 =
* First release: pre-order toggle with cutoff and release dates for simple products and
  variations, purchasability rules, front-end label and button text, cart/checkout/order line
  item, settings page, HPOS and cart/checkout blocks compatibility.

== Upgrade Notice ==

= 0.1.1 =
Fixes variation pre-order label/button text and shop listing indication for variable products.

= 0.1.0 =
First release.

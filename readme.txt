=== Ceacliu Product Options Lite ===
Contributors: ceacliu
Tags: product options, product add-ons, extra options, toppings, restaurant
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Extra toppings, sauces, sides and remarks on WooCommerce products, with prices, a live total and the choices on every order.

== Description ==

Made for pizzerias, restaurants and food shops that sell with WooCommerce. Add a group of options once, choose the products it shows on, and customers pick what they want before adding the product to the cart.

* **Checkboxes** – several choices, each with its own price (extra toppings, extras).
* **Radio buttons and drop-downs** – one choice (sauce, side, drink), with or without a price.
* **Remarks** – a free text box for the customer ("no onions, please").
* **Required fields and "at most" limits** – e.g. "choose a sauce", "at most three toppings".
* **Live total** on the product page, with the quantity.
* **Prices checked on the server** from the saved settings, never from the browser.
* **Clear orders** – the choices show in the cart, at checkout, on the order screen and in the e-mails. Two equal products with different options stay on separate lines.
* **"Choose options" button** in the shop for products with required options.
* Classic and block cart/checkout, HPOS and "Order again".

= Full version =

Lite is complete on its own. The full version, Ceacliu Product Options, sold separately on Codester, adds more for food shops:

* sizes that set the price (32 cm / 42 cm), with an extras multiplier per size
* option groups on whole categories, with excepted products
* "at least" / "exactly" rules and toppings included for free
* one-line text fields with a price, closed groups for long lists, duplicating groups
* import from Advanced Product Fields for WooCommerce, Romanian translation

[Ceacliu Product Options on Codester](https://www.codester.com/items/75713/ceacliu-woocommerce-product-options-and-toppings)

The full version uses the same option groups: activate it and your groups keep working.

== Installation ==

1. Install and activate WooCommerce.
2. Upload the plugin and activate it.
3. Go to Products → Product options → Add option group.
4. Add fields and choices, choose the products in "Where it shows" (or tick "All products") and publish.

== Frequently Asked Questions ==

= Where do the options show? =

On the product page, above the "Add to cart" button, for the products chosen in the group (or for all products).

= Can I add sizes that change the product price? =

Lite adds prices on top of the product price. For sizes, use WooCommerce variable products – the options work with them too – or the full version.

= Does the order show the options? =

Yes. They are saved on the order lines, so the order screen, the e-mails and kitchen-ticket plugins that print order line details show them.

= What happens when I delete the plugin? =

The option groups are removed, unless the full version is still installed. Orders keep their option lines.

== Changelog ==

= 1.0.1 =
* Removed the full-version admin notice and limited the WooCommerce dependency notice to the Plugins screen.

= 1.0.0 =
* First version.

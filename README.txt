=== Plugin Name ===
Contributors: lagersystem
Version: 2.0.10
Donate link: https://lagersystem.dk
Tags: shipping, parcel pickup
Requires at least: 3.0.1
Tested up to: 6.1.1
Stable tag: 2.0.10
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Enables customer to choose pickup point in woocommerce checkout flow with google maps interface. Shows points name, address and opening hours. Saves the points data to order and sets pickup point ID as extra field in woocommerce order.
Plugin supports carriers: PostNord, GLS, Bring, DHL, UPS and DAO. Is compatable with default woocommerce shipping and flexible shipping plugin.

== Description ==

Enables customer to choose pickup point in woocommerce checkout flow with google maps interface. Shows points name, address and opening hours. Saves the points data to order and sets pickup point ID as extra field in woocommerce order.
Plugin supports carriers: PostNord, GLS, Bring, DHL, UPS and DAO. Is compatable with default woocommerce shipping and flexible shipping plugin.

== Installation ==

1. Install plugin from Wordpress repos.
2. Go to the Settings menu in your WordPress admin and select LS Parcelpickup.
3. Insert the API key for Lagersystem (there is a link to get an API key at the top of the page).
4. Insert your Google Maps API key.
5. (Optional) If you use DHL, DAO, UPS or Bring you should input the necessary data as well. For GLS or PostNord, no additional data is needed.
6. Go to your WooCommerce settings and go to the Shipping tab.
7. Edit your pickup-enabled shipping methods and make sure you add the correct carrier in the Parcelpickup Carrier dropdown. Do this for all relevant shipping metods.
8. You're all done - go ahead and test it on your webshop!


== Frequently Asked Questions ==

= What carriers are supported? =

Currently PostNord, GLS, Bring, DHL and DAO.

= Can i use Flexible Shipping? =

Yes, parcel pickup settings are added directly to flexible shipping.

= Is it completly free to use? =

Yes, does require a free API Key from lagersystem.dk to use their service. You will also need an Google Maps API key.

= What countries are supported? =

Currently only Danish is supported.

== Screenshots ==

1. Shows automatic selection from customer address. Automaticly chooses closest to address.
2. Shows customer ui for changing point from maps interface.

== Changelog ==

= 2.0.10 =
* Fixed PHP warning on checkout page when ordering non-shipment products

= 2.0 =
* Support for UPS
* Enter in checkout form will now submit form, and not open location picker
* Now works with only one shipment method
* Validation added to prevent submission without location selected.
* Close button added on mobile/tablet devices

= 1.0 =
* Initial release

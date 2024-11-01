=== GetLoy payment gateway for WooCommerce (supports iPay88, PayWay and Pi Pay) ===
Contributors: jangeekho, geekho
Donate link: https://geekho.asia/
Tags: woocommerce, payment gateway, cambodia, getloy
Requires at least: 4.2
Tested up to: 5.8.3
Requires PHP: 5.6
Stable tag: 1.3.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

WooCommerce payment gateway for accepting payments in Cambodia via GetLoy using PayWay by ABA Bank, Pi Pay or iPay88.


== Description ==
The GetLoy payment gateway for WooCommerce is payment gateway for WooCommerce that allows you to accept payments using the following payment providers:

* iPay88 (Cambodia): for payments by credit card, digital wallet or bank account to your Cambodian bank account
* PayWay by ABA Bank: for international Visa or Master Card payments to your Cambodian bank account
* Pi Pay: for online payments using Pi Pay's mobile wallet.

The plugin can be set up without the help of an IT expert in a few minutes.

You will need a (paid) subscription for GetLoy in order to use the plugin. The subscription can be purchased in [the GetLoy store](https://store.geekho.asia).

= Disclaimer =
Woo™, WooCommerce®, and WooThemes® as well as the related logos and icons are registered trademarks of [Automattic Inc.](https://automattic.com/about/)

ABA Bank™ and all associated trademarks are trademarks of [Advanced Bank of Asia Ltd.](https://www.ababank.com/disclaimer/)

Pi Pay™ is a trademark of [Pi Pay Co., Ltd.](https://www.pipay.com/)

iPay88™ is a registered trademark of [IPAY88 Holding Sdn. Bhd.](https://www.ipay88.com/) and/or it's subsidiaries.

The developers of the GetLoy payment gateway for WooCommerce plugin are not affiliated with Automattic Inc. or Advanced Bank of Asia Ltd.

== Installation ==

= Minimum Requirements =
* PHP version 5.6 or greater
* WordPress 4.2 or greater
* WooCommerce 3.2.0 or greater

= Automatic installation =
Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t need to leave your web browser. To do an automatic install of this plugin, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type “GetLoy payment gateway for WooCommerce” and click Search Plugins. Once you’ve found our eCommerce plugin you can view details about it such as the point release, rating and description. Most importantly of course, you can install it by simply clicking “Install Now”.

= Manual installation =
The manual installation method involves downloading our plugin and uploading it to your webserver via your favourite FTP application. The WordPress codex contains [instructions on how to do this here](https://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

= Updating =
Automatic updates should work like a charm; as always though, ensure you backup your site just in case.


== Frequently Asked Questions ==

= What does this plugin do? =
The GetLoy payment gateway for WooCommerce add several new payment methods to WooCommerce to accept payments from the payment providers supported by GetLoy (currently PayWay by ABA Bank and Pi Pay).

= What do I need to accept payments with this plugin? =
To use this plugin, you need a merchant account for one of the supported payment providers (currently PayWay by ABA Bank and Pi Pay) and a subscription for the GetLoy payment platform.

For more details, please visit [the official GetLoy website](https://getloy.com).

= How can I purchase a GetLoy subscription? =
To buy a subscription for the GetLoy platform, please visit [the GetLoy store](https://store.geekho.asia).

= Why should I enable the option to force SSL for checkout when using this plugin? =
While the communication between the plugin and the PayWay payment gateway is always encrypted, users may less secure about entering their credit card details on your page if they do not see the SSL/HTTPS lock symbol in the address bar.

= Is this plugin compatible with Gutenberg / WordPress 5? =
Yes - as the plugin does not interfere with any of the aspects of WordPress that will be affected by the move to Gutenberg, it will work as before with the new version.

= Which payment options can I use with this plugin and an iPay88 (Cambodia) merchant account? =
The plugin currently supports the following payment methods for iPay88 (USD only):
* Debit / Credit Card (Visa, MasterCard, JCB, Diners Club)
* UnionPay
* Pi Pay
* Wing
* Metfone eMoney
* Alipay (barcode)
* Alipay (QR code)
* Acleda XPAY (payment from Acleda bank account)

We will add further payment options as they become available in iPay88.

= Can I use this plugin for iPay88 outside Cambodia? =
No - GetLoy can currently only be used for Cambodian iPay88 merchant accounts.

= What is the PayWay accepted payment methods widget for? =
ABA Bank has recently started requiring new merchants to display a logo with the payment methods accepted via PayWay in the footer of each page of the site.

To make this easier for GetLoy users, we added a widget that will display the required logos. The logos are responsive and highly optimized, so they will look good and load fast on any device.

== Screenshots ==
1. The iPay88 gateway settings.
2. The PayWay gateway settings.
3. The Pi Pay gateway settings.
4. The payment options during checkout.
5. The iPay88 credit card payment popup.
6. The PayWay payment popup.
7. The Pi Pay payment popup.
8. The PayWay accepted payment methods widget settings.
9. The PayWay accepted payment methods widget in the page footer.

== Changelog ==
= 1.3.3 =
* Forced update for plugin repo

= 1.3.2 =
* Tested with PayWay 2.0.019

= 1.3.1 =
* Fixed an issue caused by a typo

= 1.3.0 =
* added support for PayWay 2.0.x API (see the API 2.x checkbox in settings)

= 1.2.1 =
* added missing payment method logos

= 1.2.0 =
* added separate payment options for PayWay (credit/debit card and ABA Pay) during checkout
* updated graphics for Visa, MasterCard and UnionPay
* added option to select payment card logo style matching the background color
* updated graphics for PayWay accepted payment methods widget (removed PayWay frame)
* simplified settings for PayWay and iPay88

= 1.1.2 =
* added UnionPay as supported card for PayWay
* updated graphics for PayWay accepted payment methods widget (now includes UnionPay)

= 1.1.1 =
* added PayWay accepted payment methods widget

= 1.1.0 =
* added integration for iPay88 (Cambodia)

= 1.0.6 =
* added ABA Pay logo for PayWay payment option

= 1.0.5 =
* fixed display issue in Safari when cancelling a Pi Pay payment

= 1.0.4 =
* added support for changing payment method of order
* added further transaction verification steps
* added order notes when customers start payments with a GetLoy payment method

= 1.0.3 =
* adapted checkout labels to new requirements of payment processor
* added FAQ note about Gutenberg compatibility

= 1.0.2 =
* adapted 'force SSL' warning for WooCommerce v3.4

= 1.0.1 =
* improved compatibility of payment popups with some themes

= 1.0.0 =
* first release


== Upgrade Notice ==

= 1.0.0 =
This is the first release of the plugin

=== Plugin Name ===
Contributors: trakadev
Donate link: https://sargatxet.cloud/
Tags: paymentGateway, Crypto, Cardano
Requires at least: 4.7
Tested up to: 6.1
Stable tag: 2.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Woocmmerce Payment Gateway plugin to accept payments with Cardano ADA.

== Description ==

Do you want to start accepting payments with Cardano ADA in your ecommerce ?

Sargapay Gateway provides facilities for smooth, safe and instant payments using Cardano ADA with automated exchange to
EUR/USD or any other fiat currency at direct exchange rates.

Using our payment system, your customers can easily and instantly pay for the purchases on the website you’ve
integrated with Sargapay Gateway using Cardano ADA. 

Every order will have a diferent payment address to easly manage and validate payments.

== Requirements ==

* PHP v7.4+
* WooCommerce
* WASM MIME type
* PHP ext-gd

== Frequently Asked Questions ==

= How to get the xpub ? =

**YOROI**
Go to settings and in the wallet option in the export wallet section there is the export button, a pop-up window will open below the QR is the xpub.
hexadecimal xpub format

**Adalite**
Select the Advanced option and copy the Shelley extended public key.
hexadecimal xpub format

**Daedalus**
Select option More and select Settings option in the Wallet Public Key section click on the eye to reveal the xpub.
xpub format acct_xvk

= How to setup ? =

In Wordpress admin panel in the WooCommerce section in payments Select Sargapay.
 
The first thing we have to do is configure the public key and verify that the addresses it generates match the wallet where we want to receive payments.
 
The next step is to select the necessary confirmations to accept a payment as valid.
 
Now we have to go to [Blockforst.io](https://blockfrost.io "Blockforst website") to get a api key for testnet or mainnet.
 
Select a currency in case the Woocmmerce currency is not supported.
 
Finally we have to activate the plugin and verify the network that we are going to use, if you activate the box the plugin will generate payment addresses for testnet and otherwise for mainnet.

== Screenshots ==

1. Admin Settings
2. Checkout
3. Order Made

== Changelog ==

= 2.1 =
* Add Cardano Currency to WooCommerce
* Fix markup error
* Update Testnet to Preview Testnet Network

= 2.0 =
* Admin Dashboard UI
* Enable - Disable Light Wallets

= 1.0 =
* Cardano ADA support
* Light Wallets for cardano (NAMI, ETERNL and FLINT)
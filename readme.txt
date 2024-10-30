=== Cryptocurrency Payments & Donations with Copperx ===
Contributors: copperxhq
Plugin URL: https://copperx.io/
Tags: bitcoin, ethereum, litecoin, bitcash, blockchain, commerce, crypto, cryptocurrency, crypto payments, web3, coppperx
Requires at least: 5.0
Requires PHP: 5.6
Tested up to: 6.5
Stable tag: 1.6.0
License: GPLv3+
License URI: https://www.gnu.org/licenses/gpl-3.0.html

== Description ==

Copperx is the best plugin to accept cryptocurrency payments for your e-commerce store. We support Bitcoin, Ethereum, USDC, USDT, Binance, Coinbase, and many more payment methods.

== Features ==
- **Multiple networks support:** Ethereum, Polygon, Solana, and BNB Chain (by Binance)
- Automatic currency conversion
- **Customized Branding:** Customize checkout page by adding your logo and color
- Automatic payment receipts and notifications
- **24x7 customer support** via direct chat & call
- **Wallet support:** Users can pay using Binance Pay or Coinbase Pay directly
- **Crypto Donations:** Accept crypto donations with our one click [Payment Links](https://copperx.io/use-cases/donation).

== Benefits of accepting crypto payments ==
Go global, grow your revenue, and increase profit using the power of crypto.

- **Instant Settlements:** Receive funds in your wallets instantly using the power of blockchain
- **No Chargebacks:** Crypto payments are irreversible by nature eliminate fraud & chargebacks
- **Access to new customer base:** Gain access to $1 Trillion+ crypto market and 420 million users
- **Go global from day one**: Anyone can transact with anybody across the world instantly without any intermediary
- **Low fees**: Increase your profit by up to 25% due to extremely low fees

== Customer Testimonials ==
“Copperx has been a game-changer for our business in the dynamic crypto transaction world. It’s widened our payment options by accepting ETH, Polygon, and BSC tokens, simplifying our financial processes by converting these into USDC. The integration process, backed by a prompt and supportive customer service team, was seamless. Copperx also enhanced customer convenience by offering a variety of token payment options. As we look ahead, CopperX remains a crucial partner for our growth in the evolving crypto market.”
**-  astupidmoose, Founder & CEO of [Coincards](https://coincards.com/)**

“I am delighted to share my experience with the incredible CopperX team. They possess a remarkable willingness to lend an ear and truly understand the issues their customers are striving to resolve. Their unwavering determination shines through as they go above and beyond to provide solutions that not only meet expectations but genuinely assist in accepting cryptocurrency. With CopperX, I have found a team that genuinely cares and delivers results.”
**- Gennady, [Krater](https://krater.io)**

“Copperx is a game-changer for businesses looking to streamline their blockchain transactions. Its innovative approach to building payment APIs mirrors Chainstack’s commitment to providing robust, secure, and scalable blockchain solutions. Together, we are democratizing access to blockchain technologies and fueling the evolution of business transactions in the digital era.”
**- Eugene Aseev, Founder & CTO, [Chainstack](https://chainstack.com/)**


== Installation ==

= From your WordPress dashboard =

1. Visit 'Plugins > Add New'
2. Search for 'Copperx'
3. Activate Copperx from your Plugins page.

= From WordPress.org =

1. Download Copperx.
2. Upload to your '/wp-content/plugins/' directory, using your favorite method (ftp, sftp, scp, etc...)
3. Activate Copperx from your Plugins page.

= Once Activated =

1. Go to WooCommerce > Settings > Payments
2. Configure the plugin for your store

= Configuring Copperx =

* First, create an account on https://dashboard.copperx.io/ using your email. 
* Set your withdrawal address (https://dashboard.copperx.io/settings/withdrawal) and upload your brand logo and store name for the checkout page. This will personalize your checkout experience and give your customers peace of mind when making purchases.
* Within your WordPress admin area, navigate to the WooCommerce > Settings > Payments page. Here, you'll see Copperx listed in the table of payment gateways. Enable the plugin to start accepting cryptocurrency payments.

Once you're on the Copperx Plugin Page, click on the "Continue" button to configure the plugin for your store. You'll see a few options to set up, including:

= General Settings =

Enable/disable the Copperx payment method at checkout and enter your API key, which allows your website to periodically check Copperx for payment confirmation.

= Display Settings =

Customize the title and description of the payment method that will appear on the checkout page.

= Take Additional Info =

Select additional information to collect from customers on the checkout page, such as name, email, phone number, billing details, or shipping details.

Using an API key allows your website to periodically check Copperx for payment confirmation.

= Webhook Settings =

To instantly update the payment status of orders on WooCommerce, it's necessary to configure the webhook on the Copperx dashboard. Follow the instructions provided in this Loom video to learn how: Loom Video - Configuring Webhook for Order Payment Status Update(https://www.loom.com/share/97de9a12db2347e886006b4d7d535aaf?sid=0aa545ca-0100-448b-b6ea-80dd0cd2f364).

= Debug log =

Enable this mode to save payment error logs in WooCommerce > Status > Log.

That's it! With the Copperx WooCommerce Plugin, you can start accepting cryptocurrency payments from customers using USDC, USDT, BUSD, DAI, ETH, MATIC, BNB, and SOL. Get started today and embrace the future of e-commerce.


== Frequently Asked Questions ==

= What cryptocurrencies does the plugin support?

The plugin supports cryptocurrencies like Bitcoin, Ethereum, USDC, USDT, MATIC, BNB and many more.

= Do you support Binance Pay and Coinbase Pay?

Yes. We support Binance Pay and Coinbase Pay so that users can easily pay with their exchange wallets.

= How can I enable multi-chain payment options during the checkout process?

To accept payment across multiple networks, you need to add the withdrawal address at https://dashboard.copperx.io/settings/withdrawal. Once you set your withdrawal address, it will be automatically displayed on our payment page.

= Prerequisites=

To use this plugin with your WooCommerce store you will need:
* WooCommerce plugin

== Screenshots ==

1. Add products to cart
2. Checkout with Cryptocurrency
3. Copperx dashboard for managing payments

== Changelog ==

= 1.6.0 =
* Updated to support WooCommerce version up to 9.1.2 and WordPress version up to 6.5

= 1.5.1 =
* Increased copperx API request timeout value

= 1.5.0 =
* Added support for aud, sgd currencies support

= 1.4.1 =
* Fix regarding collecting billing information in checkout session

= 1.4.0 =
* Improved product name and description is displayed on checkout session page
* Added support for `copperx_product_name` a special production attribute to set different product name in checkout session and receipt compare to actual product name

= 1.3.0 =
* Introducing webhook-based payment confirmation for quicker order status updates.
* Enhanced the test mode warning to ensure users are aware of its activation.

= 1.2.0 =
* Added support for inr currency
* Improved checkout session payment option description

= 1.1.0 =
* Added support for test mode
* Added support for cad, eur, gbp currencies support

= 1.0.2 =
* Enhanced product description on checkout session page

= 1.0.1 =
* Fix checkout session total amount wrong decimals for small amounts

= 1.0.0 =
* Released first version

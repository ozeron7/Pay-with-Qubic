=== Pay with Qubic - Qubic Coin Payment Verification ===
Contributors: ozeron7
Donate link: QUBIC WALLET ADDRESS: OIACDKQDRGLTHGTWBCBCRBQBRXNBMCOBVVCVFDTVBBFAHMRMHQIWCSSFSDUM
Tags: woocommerce, payment gateway, cryptocurrency, blockchain, qubic coin
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
WC requires at least: 4.0
WC tested up to: 8.0
Stable tag: 1.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Pay with Qubic lets WooCommerce stores accept Qubic Coin payments, verify transactions, and approve or cancel orders based on payment status.

== Description ==
**Pay with Qubic** is a WooCommerce payment verification plugin that checks payments made using Qubic Coin via the blockchain. This plugin verifies payments via the blockchain by checking transaction ID information and automatically updates order statuses, ensuring a seamless payment experience for your customers.
**Qubic Network** is a next-generation blockchain ecosystem known for its high-speed transactions and zero fees, making it ideal for modern digital commerce. Designed with scalability in mind, Qubic ensures a smooth user experience even during peak network activity. For detailed information: qubic.org

### Features:
- Accept payments with Qubic Coin.
- Automatic calculation of Qubic Coin amount
- The amount of Qubic required at checkout is shown on the checkout page and in the order details.
- Automatic expiration of the payment period (configurable timeout)
- Automatically verify payment transactions. (Transaction ID, Destination Wallet, Qus Amount) 
- Update orders as "Processing" or "Cancelled" based on the transaction status.
- Providing information about the status of the payment transaction in the admin panel.
- Displays a dynamic QR code for wallet payments.
- Supports clipboard functionality for easy wallet address copying.
- 150+ currencies are supported.
- Languages available for this plugin: Arabic, Chinese (Simplified), English, Farsi, Filipino, French, German, Hindi, Indonesian, Italian, Japanese, Korean, Dutch, Polish, Portuguese, Portuguese (Brazil), Russian, Spanish, Spanish (Mexico), Turkish, Ukrainian, Vietnamese (Other languages will be added over time and on request. Please report any omissions or mistranslations.)

== Installation ==

1. Download the plugin ZIP file.
2. Upload the ZIP file to your WordPress site via **Plugins > Add New > Upload Plugin**.
3. Activate the plugin through the **Plugins** menu in WordPress.
4. Navigate to **WooCommerce > Settings > Payments** and enable "Pay with Qubic".
5. Configure your Qubic Coin wallet address and other payment settings.

== Configuration ==

After the plugin is activated, you can make the settings by following the steps below:

1. Go to WooCommerce > Settings > Payments tab.
2. Find the ‘Pay With Qubic’ payment option and click ‘Settings’.
3. Here you can make the following settings:
   - **Wallet Address**: The Qubic Coin wallet address where payments will be made.
   - **Payment Timeout**: Enter the timeout in seconds for the payment (default: 900 seconds / 15 minutes).

== Frequently Asked Questions ==

= Does this plugin support other cryptocurrencies? =
No, this plugin is specifically designed for Qubic Coin (https://qubic.org/)

= Is this plugin free? =
Yes, this plugin is free and open-source under the GPL-2.0-or-later license.

= How to pay with Qubic Coin? =
Customers can choose to pay with Qubic Coin on the checkout page. The required amount of Qubic is automatically calculated based on the order amount. After paying with Qubic Coin, the customer completes the payment process by entering the transaction ID.

= How to calculate the amount of Qubic Coin? =
The amount of Qubic payable is calculated based on the total amount of the order using the exchange rate. The value of Qubic Coin in USD is received via the Qubic API and converted according to the total amount of the order.

= Do I need a Qubic Coin wallet to use this plugin? =
Yes, you must have a Qubic Coin wallet address to accept payments.

= What is the payment term? =
The payment period starts from the time of order creation. If payment is not made by the end of the statute of limitations, the order is automatically cancelled.

== Screenshots ==

1. **Payment Gateway Settings:** Easily configure your Qubic Coin wallet and payment options.
2. **Checkout Page:** Customers can see the total amount, wallet address, and QR code for payment.
3. **Order Verification:** Automatic transaction verification ensures accurate order updates.

== Changelog ==

= 1.1 =
* Added support for dynamic QR code generation.
* Improved clipboard functionality for copying wallet addresses.
* Enhanced compatibility with WooCommerce 8.0.

= 1.0 =
* Initial release of the plugin.

== Upgrade Notice ==

= 1.1 =
Upgrade to benefit from QR code generation and improved clipboard features.

== Support ==
Please use the support forum for any issues or feedback regarding the plugin.

== License ==

This plugin is licensed under the GPL-2.0-or-later. See [GNU General Public License](https://www.gnu.org/licenses/gpl-2.0.html) for details.

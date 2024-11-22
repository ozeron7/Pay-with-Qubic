<?php
/*
Plugin Name: Pay with Qubic - Qubic Coin Payment Verification
Description: Verifies payments made using Qubic Coin and approves or cancels the order accordingly.
Version: 1.1
Author: ozeron7
Text Domain: pwqu
Domain Path: /languages
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
WC requires at least: 4.0
WC tested up to: 8.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Tags: woocommerce, payment gateway, cryptocurrency, qubic coin, blockchain
*/
 
if (!defined('ABSPATH')) {
    exit;
}
 
// Load translation files
add_action('plugins_loaded', 'pwqu_load_textdomain');
function pwqu_load_textdomain() {
    load_plugin_textdomain('pwqu', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

// Enqueue necessary JS and CSS files for the plugin
add_action('wp_enqueue_scripts', 'pwqu_enqueue_assets');
function pwqu_enqueue_assets() {
	if (is_checkout()) {
    // Enqueue Qrious script with defer attribute
    wp_enqueue_script('qrious', esc_url(plugin_dir_url(__FILE__) . 'assets/js/qrious.min.js'), array(), '4.0.2', true);
    // Add defer attribute to the script
    add_filter('script_loader_tag', 'add_defer_or_async_attribute', 10, 2);

    // Enqueue styles
    wp_enqueue_style('pwqubic-style', esc_url(plugin_dir_url(__FILE__) . 'assets/css/style.css'), array(), '1.0.0');
    wp_enqueue_style('pwqubic-qfont-style', esc_url(plugin_dir_url(__FILE__) . 'assets/css/qfont.css'), array(), '1.0.0');
	}
} 


	
// Enqueue necessary JS and CSS files for the admin page (WooCommerce settings for PWQubic gateway)
add_action('admin_enqueue_scripts', 'pwqu_enqueue_admin_assets');
function pwqu_enqueue_admin_assets($hook) {

	// Generate a new nonce
    $nonce = wp_create_nonce('pwqu_nonce');
    
	// Add the nonce to the URL
    $url_with_nonce = add_query_arg('_wpnonce', $nonce, admin_url('admin.php?page=woocommerce_wc-settings&section=pwqu'));
	$url_components = wp_parse_url($url_with_nonce);
	parse_str($url_components['query'], $params);
	
	// Check if the current page is the WooCommerce settings page and the 'pwqu' section is active
    if ($hook == 'woocommerce_page_wc-settings' && isset($params['section']) && sanitize_text_field($params['section']) == 'pwqu') {

        // Enqueue JS and CSS files for the settings page
        wp_enqueue_script(
            'qrious', 
            esc_url(plugin_dir_url(__FILE__) . 'assets/js/qrious.min.js'), 
            array(), 
            '4.0.2', 
            true
        );
        
        //Add defer or async attribute to the script
        add_filter('script_loader_tag', 'add_defer_or_async_attribute', 10, 2);
        
        wp_enqueue_style(
            'pwqubic-admin-styles', 
            esc_url(plugin_dir_url(__FILE__) . 'assets/css/admin-style.css'), 
            array(), 
            '1.0.0'
        );
        
        wp_enqueue_style(
            'pwqubic-qfont-style', 
            esc_url(plugin_dir_url(__FILE__) . 'assets/css/qfont.css'), 
            array(), 
            '1.0.0'
        );
		}
}
// Function to add defer or async attribute to Qrious script
function add_defer_or_async_attribute($tag, $handle) {
    if ('qrious' === $handle) {
        return str_replace(' src', ' defer="defer" src', $tag);
    }
    return $tag;
} 
 

// Add settings link to the plugin's action links
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'pwqu_add_settings_link');
function pwqu_add_settings_link($links) {
    $settings_link = '<a href="admin.php?page=wc-settings&tab=checkout&section=pwqu">' . __('Settings', 'pwqu') . '</a>';
    array_unshift($links, $settings_link); // Add the link to the beginning of the links array
    return $links;
}

// Check WooCommerce compatibility
add_action('plugins_loaded', 'pwqu_init', 11);
function pwqu_init() {
    if (!class_exists('WC_Payment_Gateway')) return;

    class WC_Gateway_pwqubic extends WC_Payment_Gateway {

        public function __construct() {
            $this->id = 'pwqu';
            $this->method_title = __('Pay With Qubic', 'pwqu');
            $this->method_description = __('Allows payment using Qubic Coin.', 'pwqu');
            $this->supports = array('products');

            $this->init_form_fields();
            $this->init_settings();

            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->wallet_address = $this->get_option('wallet_address');
            $this->pay_timeout = $this->get_option('pay_timeout');

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
        }
		
public function admin_options() {
    ?>
    <div class="pwqubic-settings-container">
        <!-- Left Column: Settings Form -->
        <div class="pwqubic-settings-left">
		
<?php echo '<span class="qubic-logo"><img src="'.esc_url( plugin_dir_url( __FILE__ ) . 'assets/img/logo.png' ).'" /></span>'; ?>

<!--h2>< ?php echo esc_html($this->method_title); ?></h2-->
<p><?php esc_html_e('Settings for the Pay with Qubic payment gateway.', 'pwqu'); ?></p>
            <?php $this->generate_settings_html(); ?>
        </div>

        <!-- Right Column: Developer Support -->
        <div class="pwqubic-settings-right">
            <?php
            // Developer wallet and QR code
            $developer_wallet = 'OIACDKQDRGLTHGTWBCBCRBQBRXNBMCOBVVCVFDTVBBFAHMRMHQIWCSSFSDUM';
            ?> 
            <h3><?php esc_html_e('Support the Developer', 'pwqu'); ?></h3>
            <p><?php esc_html_e('If you find this plugin helpful, you can support the developer by sending Qubic Coins to the wallet address below.', 'pwqu'); ?></p>
            <p><strong><?php esc_html_e('Developer Wallet Address', 'pwqu'); ?>:</strong></p>
            <p class="dev-wallet" style="font-family: monospace; font-size: 14px;"><?php echo esc_html($developer_wallet); ?></p>
<?php echo '<div style="text-align:center;"><span class="pwqubic-copy-button" id="copy-wallet-button">' . esc_html(__('Copy', 'pwqu')) . '</span>';
echo '<span class="copy-feedback" id="copy-feedback" style="display:none;">' . esc_html(__('Copied!', 'pwqu')) . '</span></div>'; ?>			
			
            <canvas id="developer-qr-code" style="margin-top: 20px;"></canvas>
            <p style="margin-top: 10px; font-size: 12px; color: #666;">
                <?php esc_html_e('Scan the QR code to send Qubic Coins directly to the developer.', 'pwqu'); ?>
            </p>
        </div>
    </div>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Generate QR code
            new QRious({
                element: document.getElementById('developer-qr-code'),
                value: '<?php echo esc_js($developer_wallet); ?>',
                size: 150
            });
		// Copy wallet address
		$('#copy-wallet-button').click(function() {
        var walletAddress = $('.dev-wallet').text();
        navigator.clipboard.writeText(walletAddress).then(function() {
            $('#copy-feedback').fadeIn().delay(3000).fadeOut();
        }).catch(function(error) {
            alert('<?php echo esc_js(__('An error occurred while copying the wallet address.', 'pwqu')); ?>');
        });
    });	
        });
    </script>
    <?php
}


		

        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => '',
                    'type' => 'checkbox',
                    'label' => __('Enable Pay with Qubic payment gateway', 'pwqu'),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __('Title', 'pwqu'),
                    'type' => 'text',
                    'description' => __('The title displayed during checkout', 'pwqu'),
                    'default' => __('Pay with Qubic Coin', 'pwqu'),
                    'desc_tip' => true,
                ),
                'description' => array(
                    'title' => __('Description', 'pwqu'),
                    'type' => 'textarea',
                    'description' => __('The description displayed during checkout', 'pwqu'),
                    'default' => __('Pay using Qubic Coin.', 'pwqu'),
                ),
                'wallet_address' => array(
                    'title' => __('Qubic Coin Wallet Address', 'pwqu'),
                    'type' => 'text',
                    'description' => __('The Qubic Coin address to receive payments', 'pwqu'),
                    'default' => '',
                    'desc_tip' => true,
                ),
                'pay_timeout' => array(
                    'title' => __('Payment Timeout', 'pwqu'),
                    'type' => 'text',
                    'description' => __('Specify in seconds. For example, 600 for 10 minutes', 'pwqu'),
                    'default' => '900',
                    'desc_tip' => true,
                ),
            );
        }

        public function process_payment($order_id) {
            $order = wc_get_order($order_id);
            $qubic_price_in_usd = pwqu_get_qubic_to_usd_price();
            if (!$qubic_price_in_usd) {
                wc_add_notice(__('Unable to fetch Qubic price information.', 'pwqu'), 'error');
                return;
            }

            $currency = get_woocommerce_currency();
            $order_total = $order->get_total();
            $order_total_in_usd = pwqu_convert_to_usd($order_total, $currency);

            if (!$order_total_in_usd) {
                wc_add_notice(__('Currency conversion failed.', 'pwqu'), 'error');
                return;
            }

            $order_total_in_qubic = ceil($order_total_in_usd / $qubic_price_in_usd);
            $order->update_meta_data('qubic_total_amount', $order_total_in_qubic);
            $order->update_meta_data('qubic_price_usd', $qubic_price_in_usd);
            $order->save();

            $order->update_status('pending', __('Awaiting payment with Qubic Coin.', 'pwqu'));

            return array(
                'result' => 'success',
                'redirect' => $order->get_checkout_payment_url(true)
            );
        }

        public function payment_fields() {
            if (is_checkout()) {
                global $woocommerce;
                $order_total = $woocommerce->cart->total;

                $qubic_price_in_usd = pwqu_get_qubic_to_usd_price();
                $currency = get_woocommerce_currency();
                $order_total_in_usd = pwqu_convert_to_usd($order_total, $currency);

                if ($qubic_price_in_usd && $order_total_in_usd) {
                    $order_total_in_qubic = ceil($order_total_in_usd / $qubic_price_in_usd);
				echo '<p>'.esc_html( __('Required Qus amount for payment', 'pwqu')).': <strong><span class="qu-qubic"></span>'.esc_html($order_total_in_qubic).' Qus</strong></p>';
                } else {
                    echo '<p>' . esc_html( __('Unable to fetch Qubic price information.', 'pwqu')) . '</p>';
                }
            }
        }

        public function receipt_page($order_id) {
	$order = wc_get_order($order_id);
            if (!$order) {
                echo '<p>' . esc_html( __('Invalid order.', 'pwqu')) . '</p>';
                return;
            }

	// Order details
    $qubic_total = $order->get_meta('qubic_total_amount');
    $wallet_address = esc_html($this->wallet_address);
	
	// Order creation time (as Unix Timestamp)
    $order_created_time = strtotime($order->get_date_created());
    $pay_timeout = $this->pay_timeout ? intval($this->pay_timeout) : 900; // Payment timeout in seconds
	
	// Form and notification areas
    echo '<div class="pwqubic-receipt">';
    echo '<div class="pwqubic-left-column">';
    echo '<span class="qubic-logo"><img src="'.esc_url( plugin_dir_url( __FILE__ ) . 'assets/img/logo.png' ).'" /></span>';
	echo '<p class="for-button-text">' . esc_html( __("Amount to be sent to the Qubic wallet", 'pwqu')) . ': <br /><strong><span class="qu-qubic"></span><span id="qus-amount">'.esc_html($qubic_total).'</span> Qus</strong> <button class="pwqubic-amount-copy-button" id="copy-amount-button">'. esc_html(__('Copy', 'pwqu')) .'</button> <span class="copy-amount-feedback" id="copy-amount-feedback" style="display:none;">' . esc_html(__('Copied!', 'pwqu')) . '</span></p>';

    echo '<form id="pwqubic-transaction-form">';
    echo '<p class="for-button-text">' . esc_html( __("Enter your Transaction ID and click 'Pay':", 'pwqu')) . '</p>';
    echo '<input type="text" id="pwqu_transaction_id" class="pwqubic-input" placeholder="' . esc_attr__('Transaction ID', 'pwqu') . '" required />';
    echo '<div class="pwqubic-button-container">';
    echo '<button type="button" id="pwqu_confirm_payment" class="pwqubic-button">' . esc_html__('Pay', 'pwqu') . '</button>';
    echo '<span id="countdown" class="pwqubic-timer">' . esc_html( __('Remaining time', 'pwqu')) . ': <span id="timer"></span></span>';
    echo '</div>';
    echo '<div id="loading-spinner" style="display: none;"><div class="spinner"></div> <span class="spinner-text">' . esc_html( __('Checking payment...', 'pwqu')) . '</span></div>';
    echo '</form>';
    echo '<div id="pwqu_status" class="pwqubic-status"></div>';
    echo '</div>';

    echo '<div class="pwqubic-right-column">';
    echo '<canvas id="pwqubic-qrcode"></canvas>';
    echo '<div class="pwqubic-wallet-container">';
    echo '<p class="pwqubic-wallet-address">' . esc_html( __('QUBIC Wallet Address', 'pwqu')) . ': <span id="qubic-wallet-address">' . esc_html($wallet_address) . '</span></p>';
    echo '</div>';
    echo '<button class="pwqubic-copy-button" id="copy-wallet-button">' . esc_html( __('Copy', 'pwqu')) . '</button>';
    echo '<span class="copy-feedback" id="copy-feedback" style="display:none;">' . esc_html( __('Copied!', 'pwqu')) . '</span>';
    echo '</div>';
    echo '</div>';
?>
<script type="text/javascript">
	jQuery(document).ready(function($) {
	// QR Code generation
    var qr = new QRious({
        element: document.getElementById('pwqubic-qrcode'),
        value: '<?php echo esc_js($wallet_address); ?>',
        size: 150
    });

	// Copy wallet address
    $('#copy-wallet-button').click(function() {
        var walletAddress = $('#qubic-wallet-address').text();
        navigator.clipboard.writeText(walletAddress).then(function() {
            $('#copy-feedback').fadeIn().delay(3000).fadeOut();
        }).catch(function(error) {
            alert('<?php echo esc_js(__('An error occurred while copying the wallet address.', 'pwqu')); ?>');
        });
    });
	// Copy qubic amount
    $('#copy-amount-button').click(function() {
        var walletAddress = $('#qus-amount').text();
        navigator.clipboard.writeText(walletAddress).then(function() {
            $('#copy-amount-feedback').fadeIn().delay(3000).fadeOut();
        }).catch(function(error) {
            alert('<?php echo esc_js(__('An error occurred while copying qus amounts.', 'pwqu')); ?>');
        });
    });		

	var orderCreatedTime = <?php echo esc_js($order_created_time); ?> * 1000; // Convert to milliseconds
    var timeLimit = <?php echo esc_js($pay_timeout); ?> * 1000; // Payment timeout in milliseconds
    var endTime = orderCreatedTime + timeLimit;
    var countdownTimer;
    var order_id = '<?php echo esc_js($order_id); ?>';
	
	function startCountdown() {
        countdownTimer = setInterval(function() {
            var now = new Date().getTime();
            var timeLeft = endTime - now;
            if (timeLeft <= 0) {
                clearInterval(countdownTimer);
                $('#pwqu_status').text('<?php echo esc_js(__('Payment time expired. Canceling the order...', 'pwqu')); ?>');
                cancelOrder();
            } else {
// Day, hour, minute and second calculations
var days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
var hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
var minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
var seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
var timeParts = [];
if (days > 0) {
    timeParts.push(days + (days > 1 ? '<?php echo esc_js(__('days', 'pwqu')); ?>' : '<?php echo esc_js(__('day', 'pwqu')); ?>'));
} 
if (hours > 0) {
    timeParts.push(hours + (hours > 1 ? '<?php echo esc_js(__('hours', 'pwqu')); ?>' : '<?php echo esc_js(__('hour', 'pwqu')); ?>'));
}
if (minutes > 0) {
    timeParts.push(minutes + (minutes > 1 ? '<?php echo esc_js(__('mins', 'pwqu')); ?>' : '<?php echo esc_js(__('min', 'pwqu')); ?>'));
}
timeParts.push((seconds < 10 ? '0' + seconds : seconds) + (seconds > 1 ? '<?php echo esc_js(__('secs', 'pwqu')); ?>' : '<?php echo esc_js(__('sec', 'pwqu')); ?>'));

$('#timer').text(timeParts.join(' '));
            }
        }, 1000);
    }
    startCountdown();
 
function cancelOrder() {
    $.post('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
        action: 'pwqu_cancel_order',
        order_id: '<?php echo esc_js($order_id); ?>',
        _wpnonce: '<?php echo esc_js(wp_create_nonce("pwqu_nonce")); ?>'
    }, function(response) {
        if (response.success) {
            $('#pwqu_status').text('<?php echo esc_js(__('Order canceled due to payment timeout.', 'pwqu')); ?>');
            $('#pwqu_confirm_payment, #countdown, #pwqu_transaction_id, .for-button-text').hide();
            $('.pwqubic-status').addClass('error');
        } else {
            $('#pwqu_status').text(response.data.message);
            $('.pwqubic-status').addClass('error');
        }
    });
}

    $('#pwqu_confirm_payment').click(function() {
        clearInterval(countdownTimer); // Stop countdown on payment attempt
		$('.pwqubic-status').removeClass('error');
		$('#pwqu_status').text('');
		
	var transactionId = $('#pwqu_transaction_id').val();
    if (!transactionId || transactionId.length < 60) {
        $('#pwqu_status').text('<?php echo esc_js( __('Invalid transaction ID.', 'pwqu')); ?>');
        startCountdown(); // Restart countdown if invalid
        return;
    }		
	
	// Show loading spinner and message
    $('#loading-spinner').show();
    $('#pwqu_confirm_payment').prop('disabled', true);
	
	
$.post('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
        action: 'pwqu_check_transaction',
        transaction_id: transactionId,
        order_id: '<?php echo esc_js($order_id); ?>',
		wallet_id: '<?php echo esc_js($this->wallet_address); ?>',
        _wpnonce: '<?php echo esc_js(wp_create_nonce("pwqu_nonce")); ?>'
    }, function(response) {
        if (response.success === true) {
            clearInterval(countdownTimer); // Stop countdown after successful payment
            $('#pwqu_status').text(response.data.message);
            $('#pwqu_confirm_payment, #countdown, #loading-spinner, #pwqu_transaction_id, .for-button-text').hide();
            $('.pwqubic-status').addClass('success');
        } else {
			 
				if (response.data.status_code == '0') {
				$('#pwqu_confirm_payment, #countdown, #loading-spinner, #pwqu_transaction_id, .for-button-text, .pwqubic-right-column').hide();
				}
            startCountdown(); // Resume countdown if payment failed
            $('#pwqu_status').text(response.data.message);
            $('#loading-spinner').hide();
            $('#pwqu_confirm_payment').prop('disabled', false);
            $('.pwqubic-status').addClass('error');
        }
    });
});	
          
    }); 
</script>
<?php
        }
    } 
	 
	// Add AJAX action for validating transactions
	function pwqu_check_transaction() {
	if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'pwqu_nonce')) {
    wp_send_json_error(['message' => __('Nonce verification failed.', 'pwqu')]);
    return;
	}
		
    if (!isset($_POST['transaction_id']) || !isset($_POST['order_id'])) {
        wp_send_json_error(['message' => __('Invalid data.', 'pwqu')]);
        return;
    }

	$transaction_id = sanitize_text_field(wp_unslash($_POST['transaction_id']));

    $order_id = intval($_POST['order_id']); 
    $order = wc_get_order($order_id);

    if (!$order) {
		wp_send_json_error(['message' => __('Order not found', 'pwqu')]);
		return;
    } 
	
	if ($order->get_status() !== 'pending') {
	wp_send_json_error(['message' => __('Order Status', 'pwqu') . ':' . $order->get_status()]);
	return;
	}
    
	$response = wp_remote_get("https://rpc.qubic.org/v2/transactions/{$transaction_id}");
    $transaction_data = json_decode(wp_remote_retrieve_body($response), true);
	
	if (is_wp_error($response)) {
		wp_send_json_error(['message' => __('Unable to connect to the server, please try again in a moment.', 'pwqu')]);
		return;
    }
	if (isset($transaction_data['code']) && $transaction_data['code'] == '5') {
		wp_send_json_error(['message' => __('Transaction ID was entered incorrectly.', 'pwqu')]);
		return; 
    }
	
	// Check ‘moneyFlew’
    if (empty($transaction_data['moneyFlew']) || $transaction_data['moneyFlew'] !== true) {
		wp_send_json_error(['message' => __('Payment could not be verified. Please try again.', 'pwqu')]);
		return;
    }
	
	// compare order-transaction times
	$order_created_time = strtotime($order->get_date_created());
	$order_time_ms = $order_created_time * 1000;	
	if ( $transaction_data['timestamp'] <= $order_time_ms ) {
	wp_send_json_error(['message' => __('The transaction time cannot be before the order time.', 'pwqu')]);
	return;
	} 
	
	// wallet matching
	if (!isset($_POST['wallet_id'])) {
		wp_send_json_error(['message' => __('Wallet address not recognised.', 'pwqu')]);
		return;
    } 
	
	$wallet_id = sanitize_text_field(wp_unslash($_POST['wallet_id']));
	$destId = $transaction_data['transaction']['destId'];
	
	if ( $wallet_id !== $destId ) {
	wp_send_json_error(['message' => __('The destination wallet address does not match', 'pwqu')]);
	return;	
	}

    // get the ‘amount’ information and check the amount
    $actual_amount = $transaction_data['transaction']['amount'];

    // Add paid Qubic amount to the order metadata
    $order->update_meta_data('qubic_total_amount_paid', $actual_amount);
    $order->update_meta_data('qubic_transaction_id', $transaction_id);
    $order->save();
	
	$expected_amount = $order->get_meta('qubic_total_amount');
    if ($actual_amount >= $expected_amount) {
        // Payment amount is sufficient or excessive
		$message = esc_html(__('Payment successful. Your order is processing.', 'pwqu'));
        if ($actual_amount > $expected_amount) {
		$message .= esc_html( __('However, an overpayment has been made.', 'pwqu'));
		/* translators: %s: search term */
		$message .= sprintf( __('Please contact support with your order number (#%1$s) and transaction ID for a refund.', 'pwqu'), $order_id);
        }
		$order->update_status('processing', $message);	
		wp_send_json_success(['message' => $message]);
    } else {
        // Underpayment
		$message = esc_html( __('Less than the due amount has been paid. Order canceled.', 'pwqu')); 
		/* translators: %s: search term */
		$message .= sprintf( __('Please contact support with your order number (#%1$s) and transaction ID for a refund.', 'pwqu'), $order_id); 
		$order->update_status('cancelled', $message);
		wp_send_json_error([ 'message' => $message, 'status_code' => '0']);
    }
	return;
}
 
add_action('wp_ajax_pwqu_check_transaction', 'pwqu_check_transaction');
add_action('wp_ajax_nopriv_pwqu_check_transaction', 'pwqu_check_transaction');
 
// Add to WooCommerce payment gateways list
    add_filter('woocommerce_payment_gateways', 'add_pwqu_gateway');
    function add_pwqu_gateway($methods) {
        $methods[] = 'WC_Gateway_pwqubic';
        return $methods;
    }
}

// External API functions
// Function that fetches current Qubic/USD value
function pwqu_get_qubic_to_usd_price() {
    $response = wp_remote_get('https://rpc.qubic.org/v1/latest-stats');
    if (is_wp_error($response)) {
        return null;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    return isset($data['data']['price']) ? (float) $data['data']['price'] : null;
}
 
// Get the Qubic/USD value and convert WooCommerce currency to USD if needed
function pwqu_convert_to_usd($amount, $currency) {
	$currencyx = strtolower($currency);
	// Currencies that do not require conversion
	if (in_array($currencyx, ['usd', 'qus', 'qu', 'qubic'])) {
        return $amount;
    }

    // Primary API: exchangerate-api.com
    $primary_api = "https://api.exchangerate-api.com/v4/latest/USD";
    $response = wp_remote_get($primary_api);

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        // Primary API failed, switch to secondary API
        $secondary_api = "https://open.er-api.com/v6/latest/USD";
        $response = wp_remote_get($secondary_api);
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return null; // If the secondary API also failed, return null
        }
    }
 
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // Convert target currency to USD
    if (isset($data['rates'][$currency])) {
        $rate = $data['rates'][$currency];
        return $amount / $rate; // Calculate USD equivalent
    } else {
        return null; // Return null if no currency found
    }
}
add_action('woocommerce_admin_order_data_after_order_details', 'show_qubic_info_in_admin');
function show_qubic_info_in_admin($order) {
    $qubic_total = $order->get_meta('qubic_total_amount');
	$qubic_paid = $order->get_meta('qubic_total_amount_paid') ? esc_html($order->get_meta('qubic_total_amount_paid')) : 0;
	$qubic_price_usd = $order->get_meta('qubic_price_usd') ? esc_html($order->get_meta('qubic_price_usd')) : 0;
	$currency_usd = 'USD';
	$txID = $order->get_meta('qubic_transaction_id') ? esc_html($order->get_meta('qubic_transaction_id')) : '-';
	if ( !empty($txID) && $txID !== '-' ) {
    echo '<span class="form-field form-field-wide wc-order-qubic" style="overflow-wrap: anywhere; background: #F0FEFF; border:1px solid #CCFCFF; margin-top:15px; padding:10px;"><p><strong>'.esc_html(__('Qus Amount Due', 'pwqu')).':</strong> <span class="qu-qubic"></span>' . esc_html($qubic_total) . ' Qus (1 Qu = '.esc_html($qubic_price_usd . $currency_usd).')</p>';
    echo '<p><strong>'.esc_html(__('Transaction ID', 'pwqu')).':</strong> ' . esc_html($txID) . '</p>';
    echo '<p><strong>'.esc_html(__('Amount Paid', 'pwqu')).':</strong> <span class="qu-qubic"></span>' . esc_html($qubic_paid) . ' Qus</p>';
    echo '</span>';
	} 
}

// AJAX function to cancel the order
add_action('wp_ajax_pwqu_cancel_order', 'pwqu_cancel_order');
add_action('wp_ajax_nopriv_pwqu_cancel_order', 'pwqu_cancel_order');
function pwqu_cancel_order() {
	
	// Nonce verification
	if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'pwqu_nonce')) {
        wp_send_json_error(['message' => __('Nonce verification failed.', 'pwqu')]);
        return;
    }
	
    if (isset($_POST['order_id'])) {
        $order_id = intval($_POST['order_id']);
        $order = wc_get_order($order_id);
        if ($order && $order->get_status() === 'pending') {
            $order->update_status('cancelled', __('The order was canceled because the payment period has expired.', 'pwqu'));
			wp_send_json_success(['message' => __('The order was canceled because the payment period has expired.', 'pwqu')]);
        } else {
			wp_send_json_error(['message' => __('The order could not be found and could not be cancelled.', 'pwqu')]);
        } 
    } else {
		wp_send_json_error(['message' => __('Order ID is not valid.', 'pwqu')]);
    } 
} 

add_action('woocommerce_email_order_meta', 'add_qubic_details_to_email', 20, 3);
function add_qubic_details_to_email($order, $sent_to_admin, $plain_text) {
    // Only add if the payment method ‘Pay with Qubic’ is selected
    if ($order->get_payment_method() !== 'pwqu') {
        return;
    }

    // Only add if the order is complete
    if ($order->get_status() !== 'processing') {
        return;
    }
 
    // Get order metadata
    $expected_qubic = $order->get_meta('qubic_total_amount');
    $paid_qubic = $order->get_meta('qubic_total_amount_paid');
    $transaction_id = $order->get_meta('qubic_transaction_id');
    $qubic_price_usd = $order->get_meta('qubic_price_usd');
	$currency_usd = 'USD';
    // Do not attach to an email if metadata is missing
    if (!$expected_qubic || !$paid_qubic || !$transaction_id || !$qubic_price_usd) {
        return;
    }

    // Support message in case of overpayment
    $excess_payment_note = '';
    if ($paid_qubic > $expected_qubic) {
        $excess_payment_note = '<p style="color: red;"><strong>' . __('Payment successful. However, an overpayment has been made. Please contact support.', 'pwqu') . '</strong></p>';
    }

    // Generate email content
    echo '<h3>' . esc_html(__('Qubic Payment Details', 'pwqu')) . '</h3>';
    echo '<ul>';
    echo '<li><strong>' . esc_html(__('1 Qu:', 'pwqu')) . '</strong> <span class="qu-qubic"></span>' . esc_html($qubic_price_usd . $currency_usd) . '</li>';
    echo '<li><strong>' . esc_html(__('Expected', 'pwqu')) . ':</strong> <span class="qu-qubic"></span>' . esc_html($expected_qubic) . ' Qus</li>';
    echo '<li><strong>' . esc_html(__('Paid', 'pwqu')) . ':</strong> <span class="qu-qubic"></span>' . esc_html($paid_qubic) . ' Qus</li>';
    echo '<li><strong>' . esc_html( __('Transaction ID', 'pwqu')) . ':</strong> ' . esc_html($transaction_id) . '</li>';
    echo '</ul>';
    echo esc_html($excess_payment_note); // Overpayment message
} 

add_filter('woocommerce_order_formatted_line_subtotal', 'add_qubic_info_to_order_total_column', 10, 3);
function add_qubic_info_to_order_total_column($formatted_total, $item, $order) {
    if ($order->get_payment_method() === 'pwqu') {
        $expected_qubic = $order->get_meta('qubic_total_amount');
        $paid_qubic = $order->get_meta('qubic_total_amount_paid');
        $transaction_id = $order->get_meta('qubic_transaction_id');
        $qubic_price_usd = $order->get_meta('qubic_price_usd');
		$currency_usd = 'USD';
        if (!$expected_qubic || !$paid_qubic || !$transaction_id) {
            return $formatted_total;
        }   
        $qubic_info = sprintf(
            '<br><span>%s: <strong>%s Qus</strong></span><br><span>%s: <strong>%s Qus</strong></span><br><span>%s: <strong>%s</strong></span><br><span>%s <strong>%s</strong></span>',
            __('Expected', 'pwqu'),
            esc_html($expected_qubic),
            __('Paid', 'pwqu'),
            esc_html($paid_qubic),
            __('Transaction ID', 'pwqu'),
            esc_html($transaction_id),
            __('1 Qu:', 'pwqu'),
            esc_html($qubic_price_usd . $currency_usd)
        );
        return $formatted_total . $qubic_info;
    }
    return $formatted_total;
}

?>
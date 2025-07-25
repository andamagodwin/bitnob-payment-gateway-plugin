<?php
/**
 * Renders the Bitnob Gateway block with a Lightning payment form.
 */

// Get current user information
$current_user = wp_get_current_user();
$user_email = '';
$user_display_name = '';
$is_logged_in = is_user_logged_in();

if ( $is_logged_in ) {
	$user_email = $current_user->user_email;
	$user_display_name = $current_user->display_name;
}

// Get cart/checkout information from e-commerce plugins
$cart_total = 0;
$cart_total_sats = 0;
$cart_currency = 'USD';
$is_checkout = false;
$cart_items = array();
$cart_source = '';

// WooCommerce integration
if ( class_exists( 'WooCommerce' ) && function_exists( 'WC' ) ) {
	$cart_source = 'WooCommerce';
	$wc_cart = WC()->cart;
	
	if ( $wc_cart && ! $wc_cart->is_empty() ) {
		$cart_total = $wc_cart->get_total( 'edit' ); // Get numeric value without currency symbol
		$cart_currency = get_woocommerce_currency();
		$is_checkout = is_checkout() || is_cart();
		
		// Get cart items for description
		foreach ( $wc_cart->get_cart() as $cart_item ) {
			$product = $cart_item['data'];
			$cart_items[] = $product->get_name() . ' (x' . $cart_item['quantity'] . ')';
		}
		
		// Convert to satoshis (simplified conversion - you might want to use a real API)
		$btc_rate = get_transient( 'btc_usd_rate' );
		if ( ! $btc_rate ) {
			// Fallback rate - in production, fetch from a real API
			$btc_rate = 45000; // Example: 1 BTC = $45,000
			set_transient( 'btc_usd_rate', $btc_rate, HOUR_IN_SECONDS );
		}
		
		$cart_total_sats = round( ( $cart_total / $btc_rate ) * 100000000 ); // Convert to satoshis
	}
}

// Easy Digital Downloads integration
if ( class_exists( 'Easy_Digital_Downloads' ) && function_exists( 'edd_get_cart_total' ) ) {
	if ( ! $cart_source ) { // Only if WooCommerce not found
		$cart_source = 'Easy Digital Downloads';
		$edd_total = edd_get_cart_total();
		
		if ( $edd_total > 0 ) {
			$cart_total = $edd_total;
			$cart_currency = edd_get_currency();
			$is_checkout = edd_is_checkout();
			
			// Get EDD cart items
			$edd_cart = edd_get_cart_contents();
			foreach ( $edd_cart as $item ) {
				$cart_items[] = get_the_title( $item['id'] ) . ' (x' . $item['quantity'] . ')';
			}
			
			// Convert to satoshis
			$btc_rate = get_transient( 'btc_usd_rate' );
			if ( ! $btc_rate ) {
				$btc_rate = 45000;
				set_transient( 'btc_usd_rate', $btc_rate, HOUR_IN_SECONDS );
			}
			
			$cart_total_sats = round( ( $cart_total / $btc_rate ) * 100000000 );
		}
	}
}

// Generate smart description
$smart_description = 'Lightning payment';
if ( ! empty( $cart_items ) ) {
	if ( count( $cart_items ) === 1 ) {
		$smart_description = 'Payment for: ' . $cart_items[0];
	} else {
		$smart_description = 'Payment for ' . count( $cart_items ) . ' items';
	}
} elseif ( $is_checkout ) {
	$smart_description = 'Checkout payment';
}
?>

<div <?php echo get_block_wrapper_attributes(); ?> class="bitnob-gateway-container">
	<h2 class="bitnob-heading"><?php esc_html_e( 'Bitnob Payment Gateway', 'bitnobgateway' ); ?></h2>
	<p class="bitnob-description">
		<?php esc_html_e( 'Generate a Lightning invoice and get paid instantly.', 'bitnobgateway' ); ?>
	</p>

	<?php if ( $is_logged_in ) : ?>
		<div class="bitnob-user-info">
			<div class="user-welcome">
				<span class="user-icon">👋</span>
				<span class="welcome-text">
					<?php printf( esc_html__( 'Welcome back, %s!', 'bitnobgateway' ), esc_html( $user_display_name ) ); ?>
				</span>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( $cart_total > 0 ) : ?>
		<div class="bitnob-cart-info">
			<div class="cart-summary">
				<span class="cart-icon">🛒</span>
				<div class="cart-details">
					<div class="cart-total">
						<span class="cart-label"><?php esc_html_e( 'Cart Total:', 'bitnobgateway' ); ?></span>
						<span class="cart-amount"><?php echo esc_html( $cart_currency . ' ' . number_format( $cart_total, 2 ) ); ?></span>
						<span class="cart-conversion">≈ <?php echo number_format( $cart_total_sats ); ?> sats</span>
					</div>
					<div class="cart-source">
						<small><?php printf( esc_html__( 'From %s', 'bitnobgateway' ), esc_html( $cart_source ) ); ?></small>
					</div>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<form id="bitnob-form" class="bitnob-form">
		<?php wp_nonce_field( 'bitnob_ajax_nonce', 'bitnob_nonce' ); ?>
		<div class="bitnob-form-group">
			<label for="bitnob-amount">
				<?php esc_html_e( 'Amount (satoshis)', 'bitnobgateway' ); ?>
				<?php if ( $cart_total_sats > 0 ) : ?>
					<span class="auto-filled-indicator"><?php esc_html_e( '(From cart)', 'bitnobgateway' ); ?></span>
				<?php endif; ?>
			</label>
			<input 
				type="number" 
				id="bitnob-amount" 
				name="bitnob_amount" 
				value="<?php echo esc_attr( $cart_total_sats ); ?>"
				required 
				min="1"
				<?php echo $cart_total_sats > 0 ? 'readonly' : ''; ?>
			/>
			<?php if ( $cart_total_sats > 0 ) : ?>
				<small class="amount-note">
					<?php printf( esc_html__( 'Calculated from %s %s cart total. ', 'bitnobgateway' ), esc_html( $cart_currency ), number_format( $cart_total, 2 ) ); ?>
					<button type="button" class="change-amount-btn" id="change-amount-btn">
						<?php esc_html_e( 'Change', 'bitnobgateway' ); ?>
					</button>
				</small>
			<?php endif; ?>
			<span class="bitnob-field-error" id="amount-error"></span>
		</div>
		<div class="bitnob-form-group">
			<label for="bitnob-email">
				<?php esc_html_e( 'Customer Email', 'bitnobgateway' ); ?>
				<?php if ( $is_logged_in ) : ?>
					<span class="auto-filled-indicator"><?php esc_html_e( '(Auto-filled)', 'bitnobgateway' ); ?></span>
				<?php endif; ?>
			</label>
			<input 
				type="email" 
				id="bitnob-email" 
				name="bitnob_email" 
				value="<?php echo esc_attr( $user_email ); ?>"
				<?php echo $is_logged_in ? '' : 'required'; ?>
				<?php echo $is_logged_in ? 'readonly' : ''; ?>
			/>
			<?php if ( $is_logged_in ) : ?>
				<small class="email-note">
					<?php esc_html_e( 'Using your account email. ', 'bitnobgateway' ); ?>
					<button type="button" class="change-email-btn" id="change-email-btn">
						<?php esc_html_e( 'Change', 'bitnobgateway' ); ?>
					</button>
				</small>
			<?php endif; ?>
			<span class="bitnob-field-error" id="email-error"></span>
		</div>
		<div class="bitnob-form-group">
			<label for="bitnob-description"><?php esc_html_e( 'Description', 'bitnobgateway' ); ?></label>
			<input type="text" id="bitnob-description" name="bitnob_description" value="<?php echo esc_attr( $smart_description ); ?>" />
		</div>
		<button type="submit" class="bitnob-btn" id="bitnob-submit-btn">
			<span class="btn-text"><?php esc_html_e( 'Create Invoice', 'bitnobgateway' ); ?></span>
			<span class="btn-loading" style="display: none;">
				<span class="spinner"></span>
				<?php esc_html_e( 'Creating...', 'bitnobgateway' ); ?>
			</span>
		</button>
	</form>

	<!-- Loading State -->
	<div id="bitnob-loading" class="bitnob-loading" style="display: none;">
		<div class="loading-spinner"></div>
		<p><?php esc_html_e( 'Creating your Lightning invoice...', 'bitnobgateway' ); ?></p>
	</div>

	<!-- Error Messages -->
	<div id="bitnob-error" class="bitnob-message bitnob-error" style="display: none;"></div>

	<!-- Success - Invoice Display -->
	<div id="bitnob-invoice" class="bitnob-invoice" style="display: none;">
		<div class="invoice-header">
			<h3><?php esc_html_e( 'Lightning Invoice Created!', 'bitnobgateway' ); ?></h3>
			<div class="invoice-amount">
				<span class="amount-label"><?php esc_html_e( 'Amount:', 'bitnobgateway' ); ?></span>
				<span class="amount-value" id="invoice-amount"></span>
				<span class="amount-unit">sats</span>
			</div>
		</div>
		
		<div class="invoice-content">
			<div class="qr-section">
				<div class="qr-container">
					<img id="invoice-qr" src="" alt="Lightning Invoice QR Code" />
				</div>
				<p class="qr-instruction"><?php esc_html_e( 'Scan with your Lightning wallet', 'bitnobgateway' ); ?></p>
			</div>
			
			<div class="invoice-details">
				<div class="invoice-field">
					<label><?php esc_html_e( 'Description:', 'bitnobgateway' ); ?></label>
					<span id="invoice-description"></span>
				</div>
				
				<div class="invoice-field">
					<label><?php esc_html_e( 'Payment Request:', 'bitnobgateway' ); ?></label>
					<div class="payment-request-container">
						<code id="invoice-request" class="payment-request"></code>
						<button type="button" class="copy-btn" id="copy-invoice" title="<?php esc_attr_e( 'Copy to clipboard', 'bitnobgateway' ); ?>">
							<span class="copy-icon">📋</span>
							<span class="copy-text"><?php esc_html_e( 'Copy', 'bitnobgateway' ); ?></span>
						</button>
					</div>
				</div>
			</div>
		</div>
		
		<div class="invoice-actions">
			<button type="button" class="bitnob-btn bitnob-btn-secondary" id="create-another">
				<?php esc_html_e( 'Create Another Invoice', 'bitnobgateway' ); ?>
			</button>
		</div>
	</div>

	<script>
		// Pass PHP variables to JavaScript
		window.bitnobData = {
			ajaxUrl: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
			nonce: '<?php echo wp_create_nonce( 'bitnob_ajax_nonce' ); ?>',
			user: {
				isLoggedIn: <?php echo $is_logged_in ? 'true' : 'false'; ?>,
				email: '<?php echo esc_js( $user_email ); ?>',
				displayName: '<?php echo esc_js( $user_display_name ); ?>'
			},
			cart: {
				hasItems: <?php echo $cart_total > 0 ? 'true' : 'false'; ?>,
				total: <?php echo $cart_total; ?>,
				totalSats: <?php echo $cart_total_sats; ?>,
				currency: '<?php echo esc_js( $cart_currency ); ?>',
				source: '<?php echo esc_js( $cart_source ); ?>',
				isCheckout: <?php echo $is_checkout ? 'true' : 'false'; ?>,
				description: '<?php echo esc_js( $smart_description ); ?>'
			}
		};
	</script>
</div>

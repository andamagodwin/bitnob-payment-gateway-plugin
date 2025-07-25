<?php
/**
 * Renders the Bitnob Gateway block with a Lightning payment form.
 */
?>

<div <?php echo get_block_wrapper_attributes(); ?> class="bitnob-gateway-container">
	<h2 class="bitnob-heading"><?php esc_html_e( 'Bitnob Payment Gateway', 'bitnobgateway' ); ?></h2>
	<p class="bitnob-description">
		<?php esc_html_e( 'Generate a Lightning invoice and get paid instantly.', 'bitnobgateway' ); ?>
	</p>

	<form id="bitnob-form" class="bitnob-form">
		<?php wp_nonce_field( 'bitnob_ajax_nonce', 'bitnob_nonce' ); ?>
		<div class="bitnob-form-group">
			<label for="bitnob-amount"><?php esc_html_e( 'Amount (satoshis)', 'bitnobgateway' ); ?></label>
			<input type="number" id="bitnob-amount" name="bitnob_amount" required min="1" />
			<span class="bitnob-field-error" id="amount-error"></span>
		</div>
		<div class="bitnob-form-group">
			<label for="bitnob-email"><?php esc_html_e( 'Customer Email', 'bitnobgateway' ); ?></label>
			<input type="email" id="bitnob-email" name="bitnob_email" required />
			<span class="bitnob-field-error" id="email-error"></span>
		</div>
		<div class="bitnob-form-group">
			<label for="bitnob-description"><?php esc_html_e( 'Description', 'bitnobgateway' ); ?></label>
			<input type="text" id="bitnob-description" name="bitnob_description" value="Lightning payment" />
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
			nonce: '<?php echo wp_create_nonce( 'bitnob_ajax_nonce' ); ?>'
		};
	</script>
</div>

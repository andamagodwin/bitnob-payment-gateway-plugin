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

	<form method="post" class="bitnob-form">
		<?php wp_nonce_field( 'bitnob_create_invoice', 'bitnob_nonce' ); ?>
		<div class="bitnob-form-group">
			<label for="bitnob-amount"><?php esc_html_e( 'Amount (satoshis)', 'bitnobgateway' ); ?></label>
			<input type="number" name="bitnob_amount" required />
		</div>
		<div class="bitnob-form-group">
			<label for="bitnob-email"><?php esc_html_e( 'Customer Email', 'bitnobgateway' ); ?></label>
			<input type="email" name="bitnob_email" required />
		</div>
		<div class="bitnob-form-group">
			<label for="bitnob-description"><?php esc_html_e( 'Description', 'bitnobgateway' ); ?></label>
			<input type="text" name="bitnob_description" value="Lightning payment" />
		</div>
		<button type="submit" name="bitnob_submit" class="bitnob-btn"><?php esc_html_e( 'Create Invoice', 'bitnobgateway' ); ?></button>
	</form>

	<?php if ( isset( $_POST['bitnob_submit'] ) && check_admin_referer( 'bitnob_create_invoice', 'bitnob_nonce' ) ) : ?>
		<?php
			// Call API
			$satoshis    = intval( $_POST['bitnob_amount'] );
			$email       = sanitize_email( $_POST['bitnob_email'] );
			$description = sanitize_text_field( $_POST['bitnob_description'] );
			$expires     = gmdate( 'Y-m-d\TH:i:s\Z', strtotime('+1 day') );

			$response = wp_remote_post( 'https://sandboxapi.bitnob.co/api/v1/wallets/ln/createinvoice', array(
				'headers' => array(
					'Authorization' => 'Bearer sk.3a846ff0dfb8.7e7ddae08f05636a83433470b',
					'Content-Type'  => 'application/json',
					'Accept'        => 'application/json',
				),
				'body' => json_encode( array(
					'satoshis'     => $satoshis,
					'customerEmail'=> $email,
					'description'  => $description,
					'expiresAt'    => $expires,
				) ),
			) );

			if ( is_wp_error( $response ) ) {
				echo '<p style="color: red;">Error creating invoice.</p>';
			} else {
				$data = json_decode( wp_remote_retrieve_body( $response ), true );
				if ( $data['status'] ) {
					echo '<div class="bitnob-invoice">';
					echo '<p><strong>Description:</strong> ' . esc_html( $data['data']['description'] ) . '</p>';
					echo '<p><strong>Payment Request:</strong><br><code>' . esc_html( $data['data']['request'] ) . '</code></p>';
					echo '<img src="https://api.qrserver.com/v1/create-qr-code/?data=' . urlencode( $data['data']['request'] ) . '&amp;size=200x200" alt="QR Code">';
					echo '</div>';
				} else {
					echo '<p style="color: red;">Failed to create invoice: ' . esc_html( $data['message'] ) . '</p>';
				}
			}
		?>
	<?php endif; ?>
</div>

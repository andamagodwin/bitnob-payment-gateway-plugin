<?php
/**
 * Plugin Name:       Bitnob Gateway
 * Description:       Example block scaffolded with Create Block tool.
 * Version:           0.1.0
 * Requires at least: 6.7
 * Requires PHP:      7.4
 * Author:            The WordPress Contributors
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       bitnobgateway
 *
 * @package CreateBlock
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * Registers the block using a `blocks-manifest.php` file, which improves the performance of block type registration.
 * Behind the scenes, it also registers all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://make.wordpress.org/core/2025/03/13/more-efficient-block-type-registration-in-6-8/
 * @see https://make.wordpress.org/core/2024/10/17/new-block-type-registration-apis-to-improve-performance-in-wordpress-6-7/
 */
function create_block_bitnobgateway_block_init() {
	/**
	 * Registers the block(s) metadata from the `blocks-manifest.php` and registers the block type(s)
	 * based on the registered block metadata.
	 * Added in WordPress 6.8 to simplify the block metadata registration process added in WordPress 6.7.
	 *
	 * @see https://make.wordpress.org/core/2025/03/13/more-efficient-block-type-registration-in-6-8/
	 */
	if ( function_exists( 'wp_register_block_types_from_metadata_collection' ) ) {
		wp_register_block_types_from_metadata_collection( __DIR__ . '/build', __DIR__ . '/build/blocks-manifest.php' );
		return;
	}

	/**
	 * Registers the block(s) metadata from the `blocks-manifest.php` file.
	 * Added to WordPress 6.7 to improve the performance of block type registration.
	 *
	 * @see https://make.wordpress.org/core/2024/10/17/new-block-type-registration-apis-to-improve-performance-in-wordpress-6-7/
	 */
	if ( function_exists( 'wp_register_block_metadata_collection' ) ) {
		wp_register_block_metadata_collection( __DIR__ . '/build', __DIR__ . '/build/blocks-manifest.php' );
	}
	/**
	 * Registers the block type(s) in the `blocks-manifest.php` file.
	 *
	 * @see https://developer.wordpress.org/reference/functions/register_block_type/
	 */
	$manifest_data = require __DIR__ . '/build/blocks-manifest.php';
	foreach ( array_keys( $manifest_data ) as $block_type ) {
		register_block_type( __DIR__ . "/build/{$block_type}" );
	}
}
add_action( 'init', 'create_block_bitnobgateway_block_init' );

/**
 * AJAX handler for creating Bitnob Lightning invoices
 */
function bitnob_create_invoice_ajax() {
	// Verify nonce
	if ( ! wp_verify_nonce( $_POST['nonce'], 'bitnob_ajax_nonce' ) ) {
		wp_die( 'Security check failed' );
	}

	// Sanitize inputs
	$satoshis    = intval( $_POST['amount'] );
	$email       = sanitize_email( $_POST['email'] );
	$description = sanitize_text_field( $_POST['description'] );
	$expires     = gmdate( 'Y-m-d\TH:i:s\Z', strtotime('+1 day') );

	// Validate required fields
	if ( empty( $satoshis ) || empty( $email ) ) {
		wp_send_json_error( 'Amount and email are required fields.' );
	}

	// Call Bitnob API
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
		'timeout' => 30,
	) );

	if ( is_wp_error( $response ) ) {
		wp_send_json_error( 'Error connecting to payment service. Please try again.' );
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	
	if ( $data && $data['status'] ) {
		wp_send_json_success( array(
			'description' => $data['data']['description'],
			'request'     => $data['data']['request'],
			'amount'      => $satoshis,
			'qr_url'      => 'https://api.qrserver.com/v1/create-qr-code/?data=' . urlencode( $data['data']['request'] ) . '&size=300x300'
		) );
	} else {
		$error_message = isset( $data['message'] ) ? $data['message'] : 'Failed to create invoice. Please try again.';
		wp_send_json_error( $error_message );
	}
}

// Register AJAX handlers
add_action( 'wp_ajax_bitnob_create_invoice', 'bitnob_create_invoice_ajax' );
add_action( 'wp_ajax_nopriv_bitnob_create_invoice', 'bitnob_create_invoice_ajax' );

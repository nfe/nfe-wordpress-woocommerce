<?php

/**
 * WooCommerce NFe.io Custom Functions
 *
 * @author   Renato Alves
 * @package  NFe_WooCommerce
 * @version  1.0.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

function nfe_get_field( $value = '' ) {
	$nfe_fields = get_option( 'woocommerce_nfe-woo-integration_settings' );

	if ( empty( $value ) ) {
		$output = $nfe_fields;
	} else {
		$output = $nfe_fields[$value];
	}

	return $output;
}
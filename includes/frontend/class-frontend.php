<?php

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists('WC_NFe_FrontEnd') ) :

	/**
	 * WooCommerce NFe WC_NFe_FrontEnd Class.
	 *
	 * @author   NFe.io
	 * @package  WooCommerce_NFe/Class/WC_NFe_FrontEnd
	 * @version  1.0.4
	 */
	class WC_NFe_FrontEnd {

		/**
		 * Constructor
		 *
		 * @since 1.0.4
		 */
		public function __construct() {
			// Filters.
			add_filter( 'woocommerce_my_account_my_orders_columns', 			 [ $this, 'nfe_column' ] );
			add_filter( 'woocommerce_my_account_my_address_description', 		 [ $this, 'account_desc' ] );

			// Actions.
			add_action( 'woocommerce_my_account_my_orders_column_sales-receipt', [ $this, 'column_content' ] );
			add_action( 'woocommerce_order_details_after_order_table', 			 [ $this, 'column_content' ] );
			add_action( 'woocommerce_before_edit_address_form_billing', 		 [ $this, 'billing_notice' ] );
		}

		/**
		 * Notice added on the WooCommerce edit-address page
		 *
		 * @return void
		 */
		public function billing_notice() {
			if ( nfe_get_field('nfe_enable') === 'yes' ) {
				echo '<div class="woocommerce-message">' . esc_html__( 'The following address will <strong>also</strong> be used when issuing a NFe Sales Receipt.', 'woo-nfe' ) . '</div>';
			}
		}

		/**
		 * Notice added in the My Account page
		 *
		 * @return string
		 */
		public function account_desc() {
			return esc_html__( 'The following address(es) will be used on the checkout page by default and also when issuing a NFe sales receipt.', 'woo-nfe' );
		}

		/**
		 * NFe Column Header on Recent Orders
		 *
		 * @return array
		 */
		public function nfe_column( $columns ) {
			$new_column = array();

			foreach ( $columns as $column_name => $column_info ) {
				$new_columns[ $column_name ] = $column_info;

				if ( 'order-total' === $column_name ) {
					$new_columns['sales-receipt'] = esc_html__( 'Sales Receipt', 'woo-nfe' );
				}
			}

			return $new_columns;
		}

		/**
		 * NFe Sales Receipt Column Content on Recent Orders
		 *
		 * @since 1.0.9
		 *
		 * @param WC_Order $order Order object.
		 * @return void
		 */
		public function column_content( $order ) {
			// Get order information.
			$order_id   = $order->get_id();
			$nfe        = get_post_meta( $order_id, 'nfe_issued', true );

			if ( 'yes' !== nfe_get_field( 'nfe_enable' ) ) {
				return;
			}

			// Build actions.
			$actions = array();

			if ( ! empty( $nfe ) && 'Cancelled' === $nfe['status'] ) {
				$actions['woo_nfe_cancelled'] = array(
					'url'       => '#',
					'name'      => __( 'NFe Cancelled', 'woo-nfe' ),
					'action'    => 'woo_nfe_cancelled',
				);
			} elseif ( ! empty( $nfe ) && 'Issued' === $nfe['status'] ) {
				$actions['woo_nfe_emitida'] = array(
					'url'       => '#',
					'name'      => __( 'NFe Issued', 'woo-nfe' ),
					'action'    => 'woo_nfe_emitida',
				);
			} elseif ( ! empty( $nfe ) && 'CancelledFailed' === $nfe['status'] ) {
				$actions['woo_nfe_issue'] = array(
					'url'       => '#',
					'name'      => __( 'NFe Cancelling Failed', 'woo-nfe' ),
					'action'    => 'woo_nfe_issue',
				);
			} elseif ( ! empty( $nfe ) && 'IssueFailed' === $nfe['status'] ) {
				$actions['woo_nfe_issue'] = array(
					'url'       => '#',
					'name'      => __( 'NFe Issuing Failed', 'woo-nfe' ),
					'action'    => 'woo_nfe_issue',
				);
			} elseif ( ! empty( $nfe ) && in_array( $nfe['status'], nfe_processing_status(), true ) ) {
				$actions['woo_nfe_issuing'] = array(
					'url'       => '#',
					'name'      => __( 'Processing NFe', 'woo-nfe' ),
					'action'    => 'woo_nfe_issuing',
				);
			} else {
				if ( ! empty( $nfe ) && $nfe['id'] ) {
					$actions['woo_nfe_download'] = array(
						'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_nfe_download&order_id=' . $order_id ), 'woo_nfe_download' ),
						'name'      => esc_html__( 'Download NFe', 'woo-nfe' ),
						'action'    => 'woo_nfe_download',
					);
				} else {
					if ( nfe_get_field( 'issue_past_notes' ) === 'yes' ) {
						if ( nfe_issue_past_orders( $order ) && empty( $nfe['id'] ) ) {
							$actions['woo_nfe_issue'] = array(
								'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_nfe_issue&order_id=' . $order_id ), 'woo_nfe_issue' ),
								'name'      => esc_html__( 'Issue NFe', 'woo-nfe' ),
								'action'    => 'woo_nfe_issue',
							);
						} else {
							$actions['woo_nfe_expired'] = array(
								'url'       => '#',
								'name'      => esc_html__( 'Issue Expired', 'woo-nfe' ),
								'action'    => 'woo_nfe_expired',
							);
						}
					} else {
						$actions['woo_nfe_issue'] = array(
							'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_nfe_issue&order_id=' . $order_id ), 'woo_nfe_issue' ),
							'name'      => esc_html__( 'Issue NFe', 'woo-nfe' ),
							'action'    => 'woo_nfe_issue',
						);
					}
				}
			}

			if ( '0.00' === $order->get_total() ) {
				$actions = array();
			}

			foreach ( $actions as $action ) {
				printf( '<a class="button view %s" href="%s" data-tip="%s">%s</a>',
					esc_attr( $action['action'] ),
					esc_url( $action['url'] ),
					esc_attr( $action['name'] ),
					esc_attr( $action['name'] )
				);
			}
		}
	}

	return new WC_NFe_FrontEnd();

endif;

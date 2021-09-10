<?php
/**
 * Class WC_Payments_Subscription_Service
 *
 * @package WooCommerce\Payments
 */

use WCPay\Exceptions\API_Exception;
use WCPay\Logger;

/**
 * Subscriptions logic for WCPay Subscriptions
 */
class WC_Payments_Subscription_Service {

	use WC_Payments_Subscriptions_Utilities;

	/**
	 * WCPay subscriptions endpoint on server.
	 *
	 * @const string
	 */
	const SUBSCRIPTION_API_PATH = '/subscriptions';

	/**
	 * Subscription meta key used to store WCPay subscription's ID.
	 *
	 * @const string
	 */
	const SUBSCRIPTION_ID_META_KEY = '_wcpay_subscription_id';

	/**
	 * WC Payments API Client
	 *
	 * @var WC_Payments_API_Client
	 */
	private $payments_api_client;

	/**
	 * Customer Service
	 *
	 * @var WC_Payments_Customer_Service
	 */
	private $customer_service;

	/**
	 * Product Service
	 *
	 * @var WC_Payments_Product_Service
	 */
	private $product_service;

	/**
	 * Invoice Service
	 *
	 * @var WC_Payments_Invoice_Service
	 */
	private $invoice_service;

	/**
	 * The features WCPay Subscriptions Support.
	 *
	 * @var array
	 */
	private $supports = [
		'gateway_scheduled_payments',
		'subscriptions',
		'subscription_suspension',
		'subscription_reactivation',
		'subscription_cancellation',
	];

	/**
	 * A set of temporary exceptions to the limited feature support.
	 *
	 * @var array
	 */
	private $feature_support_exceptions = [];

	/**
	 * WC Payments Subscriptions Constructor
	 *
	 * @param WC_Payments_API_Client       $api_client       WC payments API Client.
	 * @param WC_Payments_Customer_Service $customer_service WC payments customer serivce.
	 * @param WC_Payments_Product_Service  $product_service  WC payments Products service.
	 * @param WC_Payments_Invoice_Service  $invoice_service  WC payments Invoice service.
	 *
	 * @return void
	 */
	public function __construct(
		WC_Payments_API_Client $api_client,
		WC_Payments_Customer_Service $customer_service,
		WC_Payments_Product_Service $product_service,
		WC_Payments_Invoice_Service $invoice_service
	) {
		$this->payments_api_client = $api_client;
		$this->customer_service    = $customer_service;
		$this->product_service     = $product_service;
		$this->invoice_service     = $invoice_service;

		if ( ! $this->is_subscriptions_plugin_active() ) {
			add_action( 'woocommerce_checkout_subscription_created', [ $this, 'create_subscription' ] );
		}

		add_action( 'woocommerce_subscription_status_cancelled', [ $this, 'cancel_subscription' ] );
		add_action( 'woocommerce_subscription_status_expired', [ $this, 'cancel_subscription' ] );
		add_action( 'woocommerce_subscription_status_on-hold', [ $this, 'suspend_subscription' ] );
		add_action( 'woocommerce_subscription_status_pending-cancel', [ $this, 'set_pending_cancel_for_subscription' ] );
		add_action( 'woocommerce_subscription_status_pending-cancel_to_active', [ $this, 'reactivate_subscription' ] );
		add_action( 'woocommerce_subscription_status_on-hold_to_active', [ $this, 'reactivate_subscription' ] );
		add_action( 'save_post_shop_subscription', [ $this, 'maybe_update_date_for_subscription' ] );

		// Save the new token on the WCPay subscription when it's added to a WC subscription.
		add_action( 'woocommerce_payment_token_added_to_order', [ $this, 'update_wcpay_subscription_payment_method' ], 10, 3 );
		add_filter( 'woocommerce_subscription_payment_gateway_supports', [ $this, 'prevent_wcpay_subscription_changes' ], 10, 3 );

		add_action( 'woocommerce_payments_changed_subscription_payment_method', [ $this, 'maybe_attempt_payment_for_subscription' ], 10, 2 );
	}

	/**
	 * Gets a WCPay subscription from a WC subscription object.
	 *
	 * @param WC_Subscription $subscription The WC subscription to get from server.
	 *
	 * @return array|bool WCPay subscription data, otherwise false.
	 */
	public function get_wcpay_subscription( WC_Subscription $subscription ) {
		$wcpay_subscription_id = self::get_wcpay_subscription_id( $subscription );

		if ( ! $wcpay_subscription_id ) {
			return false;
		}

		try {
			return $this->payments_api_client->get_subscription( $wcpay_subscription_id );
		} catch ( API_Exception $e ) {
			return false;
		}
	}

	/**
	 * Creates a WCPay subscription.
	 *
	 * @param WC_Subscription $subscription The WC order used to create a wcpay subscription on server.
	 *
	 * @return void
	 *
	 * @throws Exception Throws an exception to stop checkout processing and display message to customer.
	 */
	public function create_subscription( WC_Subscription $subscription ) {
		$checkout_error_message = __( 'There was a problem creating your subscription. Please try again or contact us for assistance.', 'woocommerce-payments' );
		$wcpay_customer_id      = $this->customer_service->get_customer_id_for_order( $subscription );

		if ( ! $wcpay_customer_id ) {
			Logger::error( 'There was a problem creating the WCPay subscription. WCPay customer ID missing.' );
			throw new Exception( $checkout_error_message );
		}

		try {
			$subscription_data = $this->prepare_wcpay_subscription_data( $wcpay_customer_id, $subscription );
			$response          = $this->payments_api_client->create_subscription( $subscription_data );

			$this->set_wcpay_subscription_id( $subscription, $response['id'] );
			$this->invoice_service->set_subscription_invoice_id( $subscription, $response['latest_invoice'] );
		} catch ( API_Exception $e ) {
			Logger::log( sprintf( 'There was a problem creating the WCPay subscription %s', $e->getMessage() ) );
			throw new Exception( $checkout_error_message );
		}
	}

	/**
	 * Cancels the WCPay subscription when it's cancelled in WC.
	 *
	 * @param WC_Subscription $subscription The WC subscription that was canceled.
	 *
	 * @return void
	 */
	public function cancel_subscription( WC_Subscription $subscription ) {
		$wcpay_subscription_id = self::get_wcpay_subscription_id( $subscription );

		if ( ! $wcpay_subscription_id ) {
			return;
		}

		try {
			$this->payments_api_client->cancel_subscription( $wcpay_subscription_id );
		} catch ( API_Exception $e ) {
			Logger::log( sprintf( 'There was a problem canceling the subscription on WCPay server: %s.', $e->getMessage() ) );
		}
	}

	/**
	 * Suspends the WCPay subscription when a WC subscription is put on-hold.
	 *
	 * @param WC_Subscription $subscription The WC subscription that was suspended.
	 *
	 * @return void
	 */
	public function suspend_subscription( WC_Subscription $subscription ) {
		$this->update_subscription( $subscription, [ 'pause_collection' => [ 'behavior' => 'void' ] ] );
	}

	/**
	 * Reactivates the WCPay subscription when the WC subscription is activated.
	 * This is done by making a request to server to unset the "cancellation at end of period" value for the WCPay subscription.
	 *
	 * @param WC_Subscription $subscription The WC subscription that was activated.
	 *
	 * @return void
	 */
	public function reactivate_subscription( WC_Subscription $subscription ) {
		$this->update_subscription(
			$subscription,
			[
				'cancel_at_period_end' => 'false',
				'pause_collection'     => '',
			]
		);
	}

	/**
	 * Marks the WCPay subscription as pending-cancel by setting the "cancellation at end of period" on the WCPay subscription.
	 *
	 * @param WC_Subscription $subscription The subscription that was set as pending cancel.
	 *
	 * @return void
	 */
	public function set_pending_cancel_for_subscription( WC_Subscription $subscription ) {
		$this->update_subscription( $subscription, [ 'cancel_at_period_end' => 'true' ] );
	}

	/**
	 * When a WC Subscription's payment method has been updated make sure we attach
	 * the new payment method ID to the WCPay subscription.
	 *
	 * If the WCPay subscription's payment method was updated while there's a failed invoice, trigger a retry.
	 *
	 * @param int              $post_id  Post ID (WC subscription ID) that had its payment method updated.
	 * @param int              $token_id Payment Token post ID stored in DB.
	 * @param WC_Payment_Token $token    Payment Token object.
	 *
	 * @return void
	 */
	public function update_wcpay_subscription_payment_method( int $post_id, int $token_id, WC_Payment_Token $token ) {
		$subscription = wcs_get_subscription( $post_id );

		if ( $subscription ) {
			$wcpay_subscription_id   = $this->get_wcpay_subscription_id( $subscription );
			$wcpay_payment_method_id = $token->get_token();

			if ( $wcpay_subscription_id && $wcpay_payment_method_id ) {
				try {
					$this->update_subscription( $subscription, [ 'default_payment_method' => $wcpay_payment_method_id ] );
				} catch ( API_Exception $e ) {
					Logger::error( sprintf( 'There was a problem updating the WCPay subscription\'s default payment method on server: %s.', $e->getMessage() ) );
					return;
				}
			}
		}
	}

	/**
	 * Updates the next payment or trial end dates for a WCPay Subscription.
	 *
	 * @param int $post_id WC Subscription ID.
	 *
	 * @return void
	 */
	public function maybe_update_date_for_subscription( int $post_id ) {
		if ( empty( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( wc_clean( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
			return;
		}

		$subscription = wcs_get_subscription( $post_id );

		// Check for new trial end date.
		if ( array_key_exists( 'trial_end_timestamp_utc', $_POST ) && (int) $_POST['trial_end_timestamp_utc'] !== $subscription->get_time( 'trial_end' ) ) {
			$timestamp = empty( $_POST['trial_end_timestamp_utc'] ) ? 'now' : (int) $_POST['trial_end_timestamp_utc'];
			$this->set_trial_end_for_subscription( $subscription, $timestamp );
			return; // Trial end should be equal to next payment, so we can return early.
		}

		// Check for new next payment date.
		if ( array_key_exists( 'next_payment_timestamp_utc', $_POST ) && (int) $_POST['next_payment_timestamp_utc'] !== $subscription->get_time( 'next_payment' ) ) {
			$timestamp = empty( $_POST['next_payment_timestamp_utc'] ) ? 'now' : (int) $_POST['next_payment_timestamp_utc'];
			$this->set_trial_end_for_subscription( $subscription, $timestamp );
		}
	}

	/**
	 * Prepares data used to create a WCPay subscription.
	 *
	 * @param string          $wcpay_customer_id WCPay Customer ID to create the subscription for.
	 * @param WC_Subscription $subscription      The WC subscription used to create the subscription on server.
	 *
	 * @return array WCPay subscription data
	 */
	private function prepare_wcpay_subscription_data( string $wcpay_customer_id, WC_Subscription $subscription ) {
		$recurring_items = $this->get_recurring_item_data_for_subscription( $subscription );
		$one_time_items  = $this->get_one_time_item_data_for_subscription( $subscription );

		$data = [
			'customer'          => $wcpay_customer_id,
			'items'             => $recurring_items,
			'add_invoice_items' => $one_time_items,
		];

		if ( self::has_delayed_payment( $subscription ) ) {
			$data['trial_end'] = max( $subscription->get_time( 'trial_end' ), $subscription->get_time( 'next_payment' ) );
		}

		return apply_filters( 'wcpay_subscriptions_prepare_subscription_data', $data );
	}

	/**
	 * Gets recurring item data from a subscription needed to create a WCPay subscription.
	 *
	 * @param WC_Subscription $subscription The WC subscription to fetch product data from.
	 *
	 * @return array|null WCPay Product data or null on error.
	 */
	public function get_recurring_item_data_for_subscription( WC_Subscription $subscription ) {
		$data = [];

		foreach ( $subscription->get_items() as $item ) {
			$product = $item->get_product();

			if ( ! WC_Subscriptions_Product::is_subscription( $product ) ) {
				continue;
			}

			$data[] = [
				'price'     => WC_Payments_Product_Service::get_stripe_price_id( $product ),
				'quantity'  => $item->get_quantity(),
				'tax_rates' => $this->get_tax_rates_for_item( $item, $subscription ),
			];
		}

		$currency       = $subscription->get_currency();
		$discount       = $subscription->get_total_discount( false );
		$items          = array_merge( $subscription->get_fees(), $subscription->get_shipping_methods() );
		$interval       = $subscription->get_billing_period();
		$interval_count = $subscription->get_billing_interval();

		if ( 'discount' && $discount ) {
			$stripe_item_id = $this->product_service->get_stripe_product_id_for_item( 'discount' );
			$price_data     = $this->format_item_price_data( $currency, $stripe_item_id, -$discount, $interval, $interval_count );
			$data[]         = [ 'price_data' => $price_data ];
		}

		foreach ( $items as $item ) {
			$stripe_item_id = $this->product_service->get_stripe_product_id_for_item( $item->get_type() );
			$unit_amount    = $item->get_total();

			if ( $unit_amount ) {
				$price_data = $this->format_item_price_data( $currency, $stripe_item_id, $unit_amount, $interval, $interval_count );
				$data[]     = [
					'price_data' => $price_data,
					'tax_rates'  => $this->get_tax_rates_for_item( $item, $subscription ),
				];
			}
		}

		return $data;
	}

	/**
	 * Gets one time item data from a subscription needed to create a WCPay subscription.
	 *
	 * @param WC_Subscription $subscription The WC subscription to fetch item data from.
	 *
	 * @return array|null WCPay Product data or null on error.
	 */
	public function get_one_time_item_data_for_subscription( WC_Subscription $subscription ) {
		$data     = [];
		$currency = $subscription->get_currency();
		$discount = $subscription->get_parent()->get_total_discount( false );
		$items    = array_merge( $subscription->get_items(), $subscription->get_parent()->get_shipping_methods() );

		if ( 'discount' && $discount ) {
			$stripe_item_id = $this->product_service->get_stripe_product_id_for_item( 'discount' );
			$price_data     = $this->format_item_price_data( $currency, $stripe_item_id, -$discount );
			$data[]         = [ 'price_data' => $price_data ];
		}

		foreach ( $items as $item ) {
			if ( $item->is_type( 'line_item' ) ) {
				$type        = 'sign_up_fee';
				$unit_amount = floatval( WC_Subscriptions_Product::get_sign_up_fee( $item->get_product() ) );
			} else {
				$type        = $item->get_type();
				$unit_amount = $item->get_total();
			}

			$stripe_item_id = $this->product_service->get_stripe_product_id_for_item( $type );

			if ( $unit_amount ) {
				$price_data = $this->format_item_price_data( $currency, $stripe_item_id, $unit_amount );
				$data[]     = [
					'price_data' => $price_data,
					'tax_rates'  => $this->get_tax_rates_for_item( $item, $subscription ),
				];
			}
		}

		return $data;
	}

	/**
	 * Prepare tax rates for a subscription item.
	 *
	 * @param WC_Order_Item   $item         Subscription order item.
	 * @param WC_Subscription $subscription A Subscription to get tax rate information from.
	 *
	 * @return array
	 */
	public function get_tax_rates_for_item( WC_Order_Item $item, WC_Subscription $subscription ) {
		$tax_rates = [];

		if ( ! wc_tax_enabled() || ! $item->get_taxes() ) {
			return $tax_rates;
		}

		$tax_rate_ids = array_keys( $item->get_taxes()['total'] );

		if ( ! $tax_rate_ids ) {
			return $tax_rates;
		}

		$tax_inclusive = wc_prices_include_tax();

		foreach ( $subscription->get_taxes() as $tax ) {
			if ( in_array( $tax->get_rate_id(), $tax_rate_ids, true ) ) {
				$tax_rates[] = [
					'display_name' => $tax->get_name(),
					'inclusive'    => $tax_inclusive,
					'percentage'   => $tax->get_rate_percent(),
				];
			}
		}

		return $tax_rates;
	}

	/**
	 * Formats item data.
	 *
	 * @param string $currency          The item's currency.
	 * @param string $stripe_product_id The item's Stripe product id.
	 * @param float  $unit_amount       The item's unit amount.
	 * @param string $interval          The item's interval. Optional.
	 * @param int    $interval_count    The item's interval count. Optional.
	 *
	 * @return array Structured invoice item array.
	 */
	private function format_item_price_data( string $currency, string $stripe_product_id, float $unit_amount, string $interval = '', int $interval_count = 0 ) : array {
		$data = [
			'currency'    => $currency,
			'product'     => $stripe_product_id,
			'unit_amount' => $unit_amount * 100,
		];

		if ( $interval && $interval_count ) {
			$data['recurring'] = [
				'interval'       => $interval,
				'interval_count' => $interval_count,
			];
		}

		return $data;
	}

	/**
	 * Updates a WCPay subscription.
	 *
	 * @param WC_Subscription $subscription The WC subscription that relates to the WCPay subscription that needs updating.
	 * @param array           $data         Data to update.
	 *
	 * @return array|null Updated wcpay subscription or null if there was an error.
	 */
	private function update_subscription( WC_Subscription $subscription, array $data ) {
		$wcpay_subscription_id = $this->get_wcpay_subscription_id( $subscription );
		$response              = null;

		if ( ! $wcpay_subscription_id ) {
			Logger::log( 'There was a problem updating the WCPay subscription in: Subscription does not contain a valid subscription ID.' );
			return;
		}

		try {
			$response = $this->payments_api_client->update_subscription( $wcpay_subscription_id, $data );
		} catch ( API_Exception $e ) {
			Logger::log( sprintf( 'There was a problem updating the WCPay subscription on server: %s', $e->getMessage() ) );
		}

		return $response;
	}

	/**
	 * Set the trial end date for the WCPay subscription (this updates both trial end as well as next payment).
	 *
	 * @param WC_Subscription $subscription WC subscription linked to the WCPay subscription that needs updating.
	 * @param int             $timestamp    Next payment or trial end timestamp in UTC.
	 *
	 * @return void
	 */
	private function set_trial_end_for_subscription( WC_Subscription $subscription, int $timestamp ) {
		$this->update_subscription( $subscription, [ 'trial_end' => $timestamp ] );
	}

	/**
	 * Attempts payment for WCPay subscription if needed.
	 *
	 * @param WC_Subscription  $subscription WC subscription linked to the WCPay subscription that maybe needs to retry payment.
	 * @param WC_Payment_Token $token        The new subscription token to assign to the invoice order.
	 *
	 * @return void
	 */
	public function maybe_attempt_payment_for_subscription( $subscription, WC_Payment_Token $token ) {

		if ( ! wcs_is_subscription( $subscription ) ) {
			return;
		}

		$wcpay_invoice_id = WC_Payments_Invoice_Service::get_pending_invoice_id( $subscription );

		if ( ! $wcpay_invoice_id ) {
			return;
		}

		$response = $this->payments_api_client->charge_invoice( $wcpay_invoice_id );

		// Rather than wait for the Stripe webhook to be received, complete the order now if it was successfully paid.
		if ( $response && isset( $response['status'] ) && 'paid' === $response['status'] ) {
			// Remove the pending invoice ID now that we know it has been paid.
			$this->invoice_service->mark_pending_invoice_paid_for_subscription( $subscription );

			$order_id = WC_Payments_Invoice_Service::get_order_id_by_invoice_id( $wcpay_invoice_id );
			$order    = $order_id ? wc_get_order( $order_id ) : false;

			if ( $order && $order->needs_payment() ) {
				// We're about to record a successful payment, temporarily remove the "is request to change payment method" flag as it prevents us from activating the subscrption via WC_Subscription::payment_complete().
				$is_change_payment_request = WC_Subscriptions_Change_Payment_Gateway::$is_request_to_change_payment;
				WC_Subscriptions_Change_Payment_Gateway::$is_request_to_change_payment = false;

				// We need to store the successful token on the order otherwise WC_Subscriptions_Change_Payment_Gateway::change_failing_payment_method() will override the successful token with the failing one.
				$order->add_payment_token( $token );
				$order->payment_complete();

				// Reinstate the "is request to change payment method" flag.
				WC_Subscriptions_Change_Payment_Gateway::$is_request_to_change_payment = $is_change_payment_request;
				wc_add_notice( __( "We've successully collected payment for your subscription using your new payment method.", 'woocommerce-payments' ) );
			}
		}
	}

	/**
	 * Whether the subscription supports a given feature.
	 *
	 * @param bool            $supported    Is feature supported.
	 * @param string          $feature      Feature flag.
	 * @param WC_Subscription $subscription WC Subscription to check if feature is supported against.
	 *
	 * @return bool
	 */
	public function prevent_wcpay_subscription_changes( bool $supported, string $feature, WC_Subscription $subscription ) {

		if ( ! self::is_wcpay_subscription( $subscription ) ) {
			return $supported;
		}

		return in_array( $feature, $this->supports, true ) || isset( $this->feature_support_exceptions[ $subscription->get_id() ][ $feature ] );
	}

	/**
	 * Checks if the WC subscription has a first payment date that is in the future.
	 *
	 * @param WC_Subscription $subscription WC subscription to check if first payment is now or delayed.
	 *
	 * @return bool Whether the first payment is delayed.
	 */
	public static function has_delayed_payment( WC_Subscription $subscription ) {
		$trial_end = $subscription->get_time( 'trial_end' );
		$has_sync  = false;

		// TODO: Check if there is a better way to see if sync date is today.
		if ( WC_Subscriptions_Synchroniser::is_syncing_enabled() && WC_Subscriptions_Synchroniser::subscription_contains_synced_product( $subscription ) ) {
			$has_sync = true;

			foreach ( $subscription->get_items( 'line_item' ) as $item ) {
				if ( WC_Subscriptions_Synchroniser::is_payment_upfront( $item->get_product() ) ) {
					$has_sync = false;
					break;
				}
			}
		}

		return $has_sync || $trial_end > time();
	}

	/**
	 * Gets the WC subscription associated with a WCPay subscription ID.
	 *
	 * @param string $wcpay_subscription_id WCPay subscription ID.
	 *
	 * @return WC_Subscription|bool The WC subscription or false if it can't be found.
	 */
	public static function get_subscription_from_wcpay_subscription_id( string $wcpay_subscription_id ) {
		global $wpdb;

		$subscription_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->prefix}postmeta
				WHERE meta_key = %s
				AND meta_value = %s",
				self::SUBSCRIPTION_ID_META_KEY,
				$wcpay_subscription_id
			)
		);

		return wcs_get_subscription( $subscription_id );
	}

	/**
	 * Gets the WCPay subscription ID from a WC subscription.
	 *
	 * @param WC_Subscription $subscription WC Subscription.
	 *
	 * @return string
	 */
	public static function get_wcpay_subscription_id( WC_Subscription $subscription ) {
		return $subscription->get_meta( self::SUBSCRIPTION_ID_META_KEY, true );
	}

	/**
	 * Sets the WCPay subscription ID meta for WC subscription.
	 *
	 * @param WC_Subscription $subscription WC Subscription to store meta against.
	 * @param string          $value        WCPay subscription ID meta value.
	 *
	 * @return void
	 */
	private function set_wcpay_subscription_id( WC_Subscription $subscription, string $value ) {
		$subscription->update_meta_data( self::SUBSCRIPTION_ID_META_KEY, $value );
		$subscription->save();
	}

	/**
	 * Determines if a given WC subscription is a WCPay subscription.
	 *
	 * @param WC_Subscription $subscription WC Subscription object.
	 *
	 * @return bool
	 */
	public static function is_wcpay_subscription( WC_Subscription $subscription ) : bool {
		return WC_Payment_Gateway_WCPay::GATEWAY_ID === $subscription->get_payment_method() && (bool) self::get_wcpay_subscription_id( $subscription );
	}

	/**
	 * Updates a subscription's next payment date to match the WCPay subscription's payment date.
	 *
	 * @param array           $wcpay_subscription The WCPay Subscription data.
	 * @param WC_Subscription $subscription       The WC Subscription object.
	 *
	 * @return void
	 */
	public function update_dates_to_match_wcpay_subscription( array $wcpay_subscription, WC_Subscription $subscription ) {
		// Temporarily allow date changes when we're updating dates to match the dates on the WCPay subscription.
		$this->set_feature_support_exception( $subscription, 'subscription_date_changes' );

		$next_payment_date = gmdate( 'Y-m-d H:i:s', $wcpay_subscription['current_period_end'] );
		$subscription->update_dates( [ 'next_payment' => $next_payment_date ] );

		$next_payment_time_difference = absint( $wcpay_subscription['current_period_end'] - $subscription->get_time( 'next_payment' ) );

		if ( $next_payment_time_difference > 0 && $next_payment_time_difference >= 12 * HOUR_IN_SECONDS ) {
			$subscription->add_order_note( __( 'The subscription\'s next payment date has been updated to match WCPay server.', 'woocommerce-payments' ) );
		}

		// Remove the 'subscription_date_changes' exception.
		$this->clear_feature_support_exception( $subscription, 'subscription_date_changes' );
	}

	/**
	 * Temporarily allows a subscription to bypass a payment gateway feature support flag.
	 *
	 * Use @see WC_Payments_Subscription_Service::clear_feature_support_exception() to clear it.
	 *
	 * @param WC_Subscription $subscription The subscription to set the exception for.
	 * @param string          $feature      The feature to allow.
	 */
	private function set_feature_support_exception( WC_Subscription $subscription, string $feature ) {
		$this->feature_support_exceptions[ $subscription->get_id() ][ $feature ] = true;
	}

	/**
	 * Clears a gateway support flag exception.
	 *
	 * Use @see WC_Payments_Subscription_Service::set_feature_support_exception() to set one.
	 *
	 * @param WC_Subscription $subscription The subscription to remove the exception for.
	 * @param string          $feature      The feature.
	 */
	private function clear_feature_support_exception( WC_Subscription $subscription, string $feature ) {
		unset( $this->feature_support_exceptions[ $subscription->get_id() ][ $feature ] );
	}
}

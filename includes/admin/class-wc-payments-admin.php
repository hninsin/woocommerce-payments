<?php
/**
 * Set up top-level menus for WCPay.
 *
 * @package WooCommerce\Payments\Admin
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Payments_Admin Class.
 */
class WC_Payments_Admin {
	/**
	 * Hook in admin menu items.
	 */
	public function __construct() {
		// Add menu items.
		add_action( 'admin_menu', array( $this, 'add_payments_menu' ), 9 );
		add_action( 'init', array( $this, 'register_payments_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_payments_scripts' ) );
	}

	/**
	 * Add payments menu items.
	 */
	public function add_payments_menu() {
		global $submenu;

		wc_admin_register_page(
			array(
				'id'         => 'wc-payments',
				'title'      => __( 'Payments', 'woocommerce-payments' ),
				'capability' => 'manage_woocommerce',
				'path'       => '/payments/deposits',
				'position'   => '55.7', // After WooCommerce & Product menu items.
			)
		);

		wc_admin_register_page(
			array(
				'id'     => 'wc-payments-deposits',
				'title'  => __( 'Deposits', 'woocommerce-payments' ),
				'parent' => 'wc-payments',
				'path'   => '/payments/deposits',
			)
		);

		wc_admin_register_page(
			array(
				'id'     => 'wc-payments-transactions',
				'title'  => __( 'Transactions', 'woocommerce-payments' ),
				'parent' => 'wc-payments',
				'path'   => '/payments/transactions',
			)
		);

		wc_admin_register_page(
			array(
				'id'     => 'wc-payments-transaction-details',
				'title'  => __( 'Payment Details', 'woocommerce-payments' ),
				'parent' => 'wc-payments-transactions',
				'path'   => '/payments/transactions/details',
			)
		);

		wc_admin_register_page(
			array(
				'id'     => 'wc-payments-disputes',
				'title'  => __( 'Disputes', 'woocommerce-payments' ),
				'parent' => 'wc-payments',
				'path'   => '/payments/disputes',
			)
		);

		wc_admin_connect_page(
			array(
				'id'        => 'woocommerce-settings-payments-woocommerce-payments',
				'parent'    => 'woocommerce-settings-payments',
				'screen_id' => 'woocommerce_page_wc-settings-checkout-woocommerce_payments',
				'title'     => __( 'WooCommerce Payments', 'woocommerce-payments' ),
			)
		);
		// Add the Settings submenu directly to the array, it's the only way to make it link to an absolute URL.
		$submenu_keys                   = array_keys( $submenu );
		$last_submenu_key               = $submenu_keys[ count( $submenu ) - 1 ];
		$submenu[ $last_submenu_key ][] = array( // PHPCS:Ignore WordPress.WP.GlobalVariablesOverride.OverrideProhibited
			__( 'Settings', 'woocommerce' ), // PHPCS:Ignore WordPress.WP.I18n.TextDomainMismatch
			'manage_woocommerce',
			admin_url(
				add_query_arg(
					array(
						'page'    => 'wc-settings',
						'tab'     => 'checkout',
						'section' => WC_Payment_Gateway_WCPay::GATEWAY_ID,
					),
					'admin.php'
				)
			),
		);

		wp_enqueue_style(
			'wcpay-admin-css',
			plugins_url( 'assets/css/admin.css', WCPAY_PLUGIN_FILE ),
			array(),
			WC_Payments::get_file_version( 'assets/css/admin.css' )
		);
	}

	/**
	 * Register the CSS and JS scripts
	 */
	public function register_payments_scripts() {
		$script_src_url      = plugins_url( 'dist/index.js', WCPAY_PLUGIN_FILE );
		$script_deps_path    = WCPAY_ABSPATH . 'dist/index.deps.json';
		$script_dependencies = file_exists( $script_deps_path )
			? json_decode( file_get_contents( $script_deps_path ) ) // PHPCS:Ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			: array();
		wp_register_script(
			'WCPAY_DASH_APP',
			$script_src_url,
			$script_dependencies,
			WC_Payments::get_file_version( 'dist/index.js' ),
			true
		);

		wp_register_style(
			'WCPAY_DASH_APP',
			plugins_url( 'dist/index.css', WCPAY_PLUGIN_FILE ),
			array( 'wc-components' ),
			WC_Payments::get_file_version( 'dist/index.css' )
		);
	}

	/**
	 * Load the assets
	 */
	public function enqueue_payments_scripts() {
		// TODO: Try to enqueue the JS and CSS bundles lazily (will require changes on WC-Admin).
		if ( wc_admin_is_registered_page() ) {
			wp_enqueue_script( 'WCPAY_DASH_APP' );
			wp_enqueue_style( 'WCPAY_DASH_APP' );
		}
	}
}

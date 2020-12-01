<?php
/**
 * Class WC_Payments_Token_Service_Test
 *
 * @package WooCommerce\Payments\Tests
 */

use PHPUnit\Framework\MockObject\MockObject;

/**
 * WC_Payments_Token_Service unit tests.
 */
class WC_Payments_Token_Service_Test extends WP_UnitTestCase {

	/**
	 * System under test.
	 *
	 * @var WC_Payments_Token_Service
	 */
	private $token_service;

	/**
	 * Mock WC_Payments_API_Client.
	 *
	 * @var WC_Payments_API_Client|MockObject
	 */
	private $mock_api_client;

	/**
	 * Mock WC_Payments_Customer_Service.
	 *
	 * @var WC_Payments_Customer_Service|MockObject
	 */
	private $mock_customer_service;

	/**
	 * Mock WC_Payments_Account.
	 *
	 * @var WC_Payments_Account|MockObject
	 */
	private $mock_account;

	/**
	 * @var int
	 */
	private $user_id = 0;

	/**
	 * Pre-test setup
	 */
	public function setUp() {
		parent::setUp();

		$this->user_id = get_current_user_id();
		wp_set_current_user( 1 );

		$this->mock_api_client       = $this->createMock( WC_Payments_API_Client::class );
		$this->mock_customer_service = $this->createMock( WC_Payments_Customer_Service::class );
		$this->mock_account          = $this->createMock( WC_Payments_Account::class );

		$this->token_service = new WC_Payments_Token_Service( $this->mock_api_client, $this->mock_customer_service, $this->mock_account );
	}

	/**
	 * Post-test teardown
	 */
	public function tearDown() {
		WC_Payments::get_gateway()->update_option( 'test_mode', 'no' );
		wp_set_current_user( $this->user_id );
		parent::tearDown();
	}

	/**
	 * Test add token to user.
	 */
	public function test_add_token_to_user() {
		$expiry_year         = intval( gmdate( 'Y' ) ) + 1;
		$mock_payment_method = [
			'id'   => 'pm_mock',
			'card' => [
				'brand'     => 'visa',
				'last4'     => '4242',
				'exp_month' => 6,
				'exp_year'  => $expiry_year,
			],
		];

		$this->mock_customer_service
			->expects( $this->atLeastOnce() )
			->method( 'get_customer_id_by_user_id' )
			->with( 1 )
			->willReturn( 'cus_12345' );

		$this->mock_customer_service->method( 'get_payment_methods_for_customer' )->willReturn( [] );

		$token = $this->token_service->add_token_to_user( $mock_payment_method, wp_get_current_user() );

		$this->assertEquals( 'woocommerce_payments', $token->get_gateway_id() );
		$this->assertEquals( 1, $token->get_user_id() );
		$this->assertEquals( 'pm_mock', $token->get_token() );
		$this->assertEquals( 'visa', $token->get_card_type() );
		$this->assertEquals( '4242', $token->get_last4() );
		$this->assertEquals( '06', $token->get_expiry_month() );
		$this->assertEquals( $expiry_year, $token->get_expiry_year() );
		$this->assertEquals( 'cus_12345', $token->get_meta( '_wcpay_customer_id' ) );
	}

	public function test_add_payment_method_to_user() {
		$expiry_year         = intval( gmdate( 'Y' ) ) + 1;
		$mock_payment_method = [
			'id'   => 'pm_mock',
			'card' => [
				'brand'     => 'visa',
				'last4'     => '4242',
				'exp_month' => 6,
				'exp_year'  => $expiry_year,
			],
		];

		$this->mock_api_client
			->expects( $this->once() )
			->method( 'get_payment_method' )
			->with( 'pm_mock' )
			->willReturn( $mock_payment_method );

		$token = $this->token_service->add_payment_method_to_user( $mock_payment_method['id'], wp_get_current_user() );

		$this->assertEquals( 'woocommerce_payments', $token->get_gateway_id() );
		$this->assertEquals( 1, $token->get_user_id() );
		$this->assertEquals( 'pm_mock', $token->get_token() );
		$this->assertEquals( 'visa', $token->get_card_type() );
		$this->assertEquals( '4242', $token->get_last4() );
		$this->assertEquals( '06', $token->get_expiry_month() );
		$this->assertEquals( $expiry_year, $token->get_expiry_year() );
	}

	public function test_woocommerce_payment_token_deleted() {
		$this->mock_api_client
			->expects( $this->once() )
			->method( 'detach_payment_method' )
			->with( 'pm_mock' )
			->will( $this->returnValue( [] ) );

		$token = new WC_Payment_Token_CC();
		$token->set_gateway_id( 'woocommerce_payments' );
		$token->set_token( 'pm_mock' );

		$this->token_service->woocommerce_payment_token_deleted( 'pm_mock', $token );
	}

	public function test_woocommerce_payment_token_deleted_other_gateway() {
		$this->mock_api_client
			->expects( $this->never() )
			->method( 'detach_payment_method' );

		$token = new WC_Payment_Token_CC();
		$token->set_gateway_id( 'another_gateway' );
		$token->set_token( 'pm_mock' );

		$this->token_service->woocommerce_payment_token_deleted( 'pm_mock', $token );
	}

	public function test_woocommerce_payment_token_set_default() {
		$this->mock_customer_service
			->expects( $this->once() )
			->method( 'get_customer_id_by_user_id' )
			->with( 1 )
			->willReturn( 'cus_12345' );

		$this->mock_customer_service
			->expects( $this->once() )
			->method( 'set_default_payment_method_for_customer' )
			->with( 'cus_12345', 'pm_mock' );

		$token = new WC_Payment_Token_CC();
		$token->set_gateway_id( 'woocommerce_payments' );
		$token->set_token( 'pm_mock' );
		$token->set_user_id( 1 );

		$this->token_service->woocommerce_payment_token_set_default( 'pm_mock', $token );
	}

	public function test_woocommerce_payment_token_set_default_other_gateway() {
		$this->mock_customer_service
			->expects( $this->never() )
			->method( 'get_customer_id_by_user_id' );

		$this->mock_customer_service
			->expects( $this->never() )
			->method( 'set_default_payment_method_for_customer' );

		$token = new WC_Payment_Token_CC();
		$token->set_gateway_id( 'another_gateway' );
		$token->set_token( 'pm_mock' );

		$this->token_service->woocommerce_payment_token_set_default( 'pm_mock', $token );
	}

	public function test_woocommerce_payment_token_set_default_no_customer() {
		$this->mock_customer_service
			->expects( $this->once() )
			->method( 'get_customer_id_by_user_id' )
			->with( 1 )
			->willReturn( null );

		$this->mock_customer_service
			->expects( $this->never() )
			->method( 'set_default_payment_method_for_customer' );

		$token = new WC_Payment_Token_CC();
		$token->set_gateway_id( 'woocommerce_payments' );
		$token->set_token( 'pm_mock' );
		$token->set_user_id( 1 );

		$this->token_service->woocommerce_payment_token_set_default( 'pm_mock', $token );
	}

	public function test_woocommerce_get_customer_payment_tokens_removes_unavailable_tokens() {
		$token1            = WC_Helper_Token::create_token( 'pm_mock0' );
		$unavailable_token = WC_Helper_Token::create_token( 'pm_mock1' );
		$token2            = WC_Helper_Token::create_token( 'pm_mock2' );

		$token1->add_meta_data( '_wcpay_customer_id', 'cus_12345' );
		$token2->add_meta_data( '_wcpay_customer_id', 'cus_12345' );
		$unavailable_token->add_meta_data( '_wcpay_customer_id', 'cus_67890' );

		$tokens = [
			$token1,
			$unavailable_token,
			$token2,
		];

		$this->mock_customer_service->method( 'get_customer_id_by_user_id' )->willReturn( 'cus_12345' );

		$this->mock_customer_service
			->expects( $this->once() )
			->method( 'get_payment_methods_for_customer' )
			->with( 'cus_12345' )
			->willReturn( [] );

		$result        = $this->token_service->woocommerce_get_customer_payment_tokens( $tokens, 1, 'woocommerce_payments' );
		$result_tokens = array_values( $result );
		$this->assertCount( 2, $result_tokens );
		$this->assertEquals( 'pm_mock0', $result_tokens[0]->get_token() );
		$this->assertEquals( 'pm_mock2', $result_tokens[1]->get_token() );
	}

	public function test_woocommerce_get_customer_payment_tokens_imports_tokens() {
		$token = WC_Helper_Token::create_token( 'pm_mock0' );
		$token->add_meta_data( '_wcpay_customer_id', 'cus_12345' );

		$tokens = [ $token ];

		$mock_payment_methods = [
			[
				'id'   => 'pm_mock1',
				'type' => 'card',
				'card' => [
					'brand'     => 'visa',
					'last4'     => '4242',
					'exp_month' => 6,
					'exp_year'  => 2026,
				],
			],
			[
				'id'   => 'pm_mock2',
				'type' => 'card',
				'card' => [
					'brand'     => 'master',
					'last4'     => '5665',
					'exp_month' => 4,
					'exp_year'  => 2031,
				],
			],
		];

		$this->mock_customer_service->method( 'get_customer_id_by_user_id' )->willReturn( 'cus_12345' );

		$this->mock_customer_service
			->expects( $this->atLeastOnce() )
			->method( 'get_payment_methods_for_customer' )
			->with( 'cus_12345' )
			->willReturn( $mock_payment_methods );

		$result        = $this->token_service->woocommerce_get_customer_payment_tokens( $tokens, 1, 'woocommerce_payments' );
		$result_tokens = array_values( $result );
		$this->assertCount( 3, $result_tokens );
		$this->assertEquals( 'pm_mock0', $result_tokens[0]->get_token() );
		$this->assertEquals( 'pm_mock1', $result_tokens[1]->get_token() );
		$this->assertEquals( 'pm_mock2', $result_tokens[2]->get_token() );
	}

	public function test_woocommerce_get_customer_payment_tokens_does_not_remove_valid_tokens() {
		$token = WC_Helper_Token::create_token( 'pm_mock0' );
		$token->add_meta_data( '_wcpay_customer_id', 'cus_12345' );

		$inactive_token = WC_Helper_Token::create_token( 'pm_mock1' );
		$inactive_token->add_meta_data( '_wcpay_customer_id', 'cus_12345_test' );

		$inexistent_token = WC_Helper_Token::create_token( 'pm_mock2' );
		$inexistent_token->add_meta_data( '_wcpay_customer_id', 'cus_random_customer' );

		$tokens = [ $token, $inactive_token, $inexistent_token ];

		$this->mock_customer_service
			->method( 'get_customer_id_by_user_id' )
			->will(
				$this->returnValueMap(
					[
						[ 1, '', 'cus_12345' ],
						[ 1, 'live', 'cus_12345' ],
						[ 1, 'test', 'cus_12345_test' ],
					]
				)
			);

		$this->mock_customer_service
			->expects( $this->atLeastOnce() )
			->method( 'get_payment_methods_for_customer' )
			->with( 'cus_12345' )
			->willReturn(
				[
					[
						'id'   => 'pm_mock0',
						'type' => 'card',
						'card' => [
							'brand'     => 'visa',
							'last4'     => '4242',
							'exp_month' => 6,
							'exp_year'  => 2026,
						],
					],
				]
			);

		$result        = $this->token_service->woocommerce_get_customer_payment_tokens( $tokens, 1, 'woocommerce_payments' );
		$result_tokens = array_values( $result );
		$this->assertCount( 1, $result_tokens );
		$this->assertEquals( 'pm_mock0', $result_tokens[0]->get_token() );

		// Assert that inactive_token has not been deleted and inexistent_token has.
		$this->assertNotNull( WC_Payment_Tokens::get( $inactive_token->get_id() ) );
		$this->assertNull( WC_Payment_Tokens::get( $inexistent_token->get_id() ) );
	}

	public function test_woocommerce_get_customer_payment_tokens_not_logged() {
		$this->mock_customer_service
			->expects( $this->never() )
			->method( 'get_customer_id_by_user_id' );

		$user_id = get_current_user_id();
		wp_set_current_user( 0 );

		$result = $this->token_service->woocommerce_get_customer_payment_tokens( [ new WC_Payment_Token_CC() ], 1, 'woocommerce_payments' );
		$this->assertEquals( [ new WC_Payment_Token_CC() ], $result );

		wp_set_current_user( $user_id );
	}

	public function test_woocommerce_get_customer_payment_tokens_other_gateway() {
		$this->mock_customer_service
			->expects( $this->never() )
			->method( 'get_customer_id_by_user_id' );

		$result = $this->token_service->woocommerce_get_customer_payment_tokens( [ new WC_Payment_Token_CC() ], 1, 'other_gateway' );
		$this->assertEquals( [ new WC_Payment_Token_CC() ], $result );
	}

	public function test_woocommerce_get_customer_payment_tokens_no_customer() {
		$token = WC_Helper_Token::create_token( 'pm_mock0' );
		$token->add_meta_data( '_wcpay_customer_id', 'cus_12345' );

		$this->mock_customer_service
			->expects( $this->atLeastOnce() )
			->method( 'get_customer_id_by_user_id' )
			->willReturn( null );

		$result = $this->token_service->woocommerce_get_customer_payment_tokens( [ $token ], 1, 'woocommerce_payments' );
		$this->assertCount( 0, $result );
	}

	public function test_woocommerce_get_customer_payment_tokens_migrates_old_tokens_live_account() {
		// We're using test mode here to assert the tokens are migrated to the live customer regardless of it.
		WC_Payments::get_gateway()->update_option( 'test_mode', 'yes' );

		$old_token1 = WC_Helper_Token::create_token( 'pm_mock0' );
		$old_token2 = WC_Helper_Token::create_token( 'pm_mock1' );
		$token      = WC_Helper_Token::create_token( 'pm_mock2' );
		$token->add_meta_data( '_wcpay_customer_id', 'cus_12345_test' );

		$tokens = [ $old_token1, $old_token2, $token ];

		$this->mock_account->method( 'get_is_live' )->willReturn( true );
		$this->mock_customer_service->method( 'get_payment_methods_for_customer' )->willReturn( [ 'id' => 'pm_mock2' ] );

		$this->mock_customer_service
			->method( 'get_customer_id_by_user_id' )
			->willReturnMap(
				[
					[ 1, '', 'cus_12345_test' ],
					[ 1, 'test', 'cus_12345_test' ],
					[ 1, 'live', 'cus_12345' ],
				]
			);

		$result = $this->token_service->woocommerce_get_customer_payment_tokens( $tokens, 1, 'woocommerce_payments' );

		$this->assertCount( 1, $result );
		$this->assertEquals( 'cus_12345_test', current( $result )->get_meta( '_wcpay_customer_id' ) );
		$this->assertEquals( 'cus_12345', WC_Payment_Tokens::get( $old_token1->get_id() )->get_meta( '_wcpay_customer_id' ) );
		$this->assertEquals( 'cus_12345', WC_Payment_Tokens::get( $old_token2->get_id() )->get_meta( '_wcpay_customer_id' ) );
	}

	public function test_woocommerce_get_customer_payment_tokens_migrates_old_tokens_live_account_only_test_customer() {
		$old_token = WC_Helper_Token::create_token( 'pm_mock0' );

		$this->mock_account->method( 'get_is_live' )->willReturn( true );

		$this->mock_customer_service
			->method( 'get_customer_id_by_user_id' )
			->withConsecutive( [ 1 ], [ 1, 'live' ], [ 1, 'test' ] )
			->willReturnOnConsecutiveCalls( null, null, 'cus_12345' );

		$result = $this->token_service->woocommerce_get_customer_payment_tokens( [ $old_token ], 1, 'woocommerce_payments' );

		$this->assertEmpty( $result );
		$this->assertEquals( 'cus_12345', WC_Payment_Tokens::get( $old_token->get_id() )->get_meta( '_wcpay_customer_id' ) );
	}

	public function test_woocommerce_get_customer_payment_tokens_migrates_old_tokens_test_account() {
		WC_Payments::get_gateway()->update_option( 'test_mode', 'yes' );
		$old_token = WC_Helper_Token::create_token( 'pm_mock0' );
		$token     = WC_Helper_Token::create_token( 'pm_mock1' );
		$token->add_meta_data( '_wcpay_customer_id', 'cus_12345_test' );

		$tokens = [ $old_token, $token ];

		$this->mock_account->method( 'get_is_live' )->willReturn( false );
		$this->mock_customer_service->method( 'get_payment_methods_for_customer' )->willReturn(
			[
				[ 'id' => 'pm_mock0' ],
				[ 'id' => 'pm_mock1' ],
			]
		);

		$this->mock_customer_service
			->method( 'get_customer_id_by_user_id' )
			->willReturnMap(
				[
					[ 1, '', 'cus_12345_test' ],
					[ 1, 'test', 'cus_12345_test' ],
				]
			);

		$result = $this->token_service->woocommerce_get_customer_payment_tokens( $tokens, 1, 'woocommerce_payments' );

		$this->assertCount( 2, $result );
		$this->assertEquals( 'cus_12345_test', $result[ $old_token->get_id() ]->get_meta( '_wcpay_customer_id' ) );
		$this->assertEquals( 'cus_12345_test', $result[ $token->get_id() ]->get_meta( '_wcpay_customer_id' ) );
	}
}

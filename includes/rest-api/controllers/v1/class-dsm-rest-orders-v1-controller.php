<?php
/**
 * REST API Orders controller
 *
 * Handles requests to the /orders endpoint.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Supplier\RestApi\v1\Controllers
 * @version 	1.0.0
 */

class DSM_REST_Orders_V1_Controller extends \WP_REST_Controller
{
	/**
	 * Endpoint namespace.
	 *
	 * @since   1.0.0
	 * @var 	string
	 */
	protected $namespace = "dsm/v1";

	/**
	 * Route base.
	 *
	 * @since   1.0.0
	 * @var 	string
	 */
	protected $rest_base = "orders";

	/**
	 * Register the routes for orders.
	 *
	 * @since   1.0.0
	 * @return  void
	 */
	public function register_routes()
	{
		register_rest_route(
			$this->namespace,
			"/" . $this->rest_base,
			[
				[
					"methods"             => WP_REST_Server::CREATABLE,
					"callback"            => [ $this, "create_item"                   ],
					"permission_callback" => [ $this, "create_item_permissions_check" ],
					"args"                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				],
				"schema" => [ $this, "get_public_item_schema" ],
			]
		);
	}

	/**
	 * Create an order from shopkeeper.
	 *
	 * @since 	1.0.0
	 * @param 	WP_REST_Request $request Full details about the request.
	 * @return 	WP_Error|WP_REST_Response
	 */
	public function create_item( $request )
	{
		$checkAuth = $this->checkAuth( $request );

		if ( !$checkAuth[ "success" ] )
		{
			$error = $checkAuth[ "error" ];
			return new WP_Error( $error[ "code" ], $error[ "message" ], $error[ "status" ] );
		}

		$user      = $checkAuth[ "user" ];
		$data      = json_decode( $request->get_body(), true );

		$checkData = [ "order_id", "products", "billing", "shipping" ];

		foreach ( $checkData as $check )
			if ( !isset( $data[ $check ] ) )
				return new WP_Error( "dsm_rest_orders_v1_missing_data", __( "Missing data body.", "woo_ds_supplier" ), 400 );

		$order = wc_create_order();

		foreach ( $data[ "products" ] as $sku => $p )
		{
			$product_id = wc_get_product_id_by_sku( $sku );
			$product    = wc_get_product( $product_id );

			$order->add_product( $product, $p[ "quantity" ] );
		}

		$order->calculate_totals();

		$order->set_address( $data[ "billing"  ], "billing"  );
		$order->set_address( $data[ "shipping" ], "shipping" );

		$order->set_customer_ip_address( $data[ "customer_ip" ] );
		$order->set_customer_id( $user->ID );

		$order->update_meta_data( "_shopkeeper_order_id", $data[ "order_id" ] );
		$order->update_meta_data( "_dsm_shopkeeper",      $user->ID           );

		$order->add_order_note( "Order from shopkeeper: {$user->user_login} ({$user->ID})" );

		$order->set_status( "wc-on-hold" );
		$order->save();

		\Dropshipping_Supplier::log( $request->get_body(), true );

		return new WP_REST_Response( [
			"code" => "dsm_rest_orders_v1_success",
		], 200 );
	}

	/**
	 * Check if a given request has access to read items.
	 *
	 * @since 	1.0.0
	 * @param  	WP_REST_Request $request Full details about the request.
	 * @return 	WP_Error|boolean
	 */
	public function create_item_permissions_check( $request )
	{
		$checkAuth = $this->checkAuth( $request );

		if ( !$checkAuth[ "success" ] )
		{
			$error = $checkAuth[ "error" ];
			return new WP_Error( $error[ "code" ], $error[ "message" ], $error[ "status" ] );
		}

		return true;
	}

	/**
	 * Check credentials sent in request
	 *
	 * @since 	1.0.0
	 * @param 	object $request
	 * @return 	array
	 */
	private function checkAuth( $request )
	{
		$email     = $request->get_header( "_dsm_email"      );
		$supp      = $request->get_header( "_dsm_supplier"   );
		$shop      = $request->get_header( "_dsm_shopkeeper" );

		if ( is_null( $email ) || is_null( $supp ) || is_null( $shop ) )
		{
			$code    = "dsm_rest_cannot_view";
			$message = __( "Sorry, you cannot list resources.", "woo_ds_supplier" );
			$status  = [ "status" => rest_authorization_required_code() ];

			return [
				"success" => false,
				"error"   => [
					"code"    => $code,
					"message" => $message,
					"status"  => $status
				]
			];
		}

		$user      = get_user_by( "email", $email );

		if ( !$user )
		{
			$code    = "dsm_rest_orders_v1_invalid_email";
			$message = __( "Invalid email", "woo_ds_supplier" );
			$status  = [ "status" => 401 ];

			return [
				"success" => false,
				"error"   => [
					"code"    => $code,
					"message" => $message,
					"status"  => $status
				]
			];
		}

		$user->usermeta = array_map( function ( $data ) {
			return reset( $data );
		}, get_user_meta( $user->ID ) );

		$_url      = $user->data->usermeta[ "_dsm_url"      ];
		$_secret   = $user->data->usermeta[ "_dsm_secret"   ];
		$_secret_s = $user->data->usermeta[ "_dsm_secret_s" ];

		$secret    = "{$user->ID}|{$user->user_email}|{$_url}|" . get_home_url();

		$s1        = $_secret   == $supp;
		$s2        = $_secret_s == $shop;

		$verify    = password_verify( $secret, $_secret );

		if ( !$s1 || !$s2 || !$verify )
		{
			$code    = "dsm_rest_orders_v1_invalid_secret";
			$message = __( "Invalid secret", "woo_ds_supplier" );
			$status  = [ "status" => 401 ];

			return [
				"success" => false,
				"error"   => [
					"code"    => $code,
					"message" => $message,
					"status"  => $status
				]
			];
		}

		return [
			"success" => true,
			"user"    => $user,
		];
	}
}

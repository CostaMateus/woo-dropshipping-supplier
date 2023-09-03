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

class DSM_REST_Orders_V1_Controller extends DSM_REST_Main_Controller
{
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
					"callback"            => [ $this, "create_item"       ],
					"permission_callback" => [ $this, "permissions_check" ],
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

		\Dropshipping_Supplier::log( $request->get_body() );

		switch ( $data[ "action" ] )
		{
			case "create":
				$response = $this->create_order( $user, $data );
			break;
		}

		return $response;
	}

	/**
	 * Get free shipping method || null
	 *
	 * @since 	1.0.0
	 * @return 	array
	 */
	private function get_shipping_method()
	{
		$shipping_methods = WC()->shipping->get_shipping_methods();

		foreach ( $shipping_methods as $key => $sm )
			if ( $key == "free_shipping" && $sm->enabled == "yes" )
				return [
					"success" => true,
					"id"      => $key,
					"title"   => $sm->title,
				];

		return [ "success" => false ];
	}

	/**
	 * Create order with given data
	 *
	 * @param 	object 	$user
	 * @param 	array 	$data
	 * @return 	void
	 */
	private function create_order( $user, $data )
	{
		$order = wc_create_order();

		foreach ( $data[ "products" ] as $sku => $p )
		{
			$product_id = wc_get_product_id_by_sku( $sku );
			$product    = wc_get_product( $product_id );

			$order->add_product( $product, $p[ "quantity" ] );
		}

		$shipping_method = $this->get_shipping_method();

		if ( $shipping_method[ "success" ] )
		{
			$shipping = new WC_Order_Item_Shipping();
			$shipping->set_method_title( $shipping_method[ "title" ] );
			$shipping->set_method_id( $shipping_method[ "id" ] );
			$order->add_item( $shipping );
		}

		$order->calculate_totals();

		$order->set_address( $data[ "billing"  ], "billing"  );
		$order->set_address( $data[ "shipping" ], "shipping" );

		$order->set_customer_ip_address( $data[ "customer_ip" ] );
		$order->set_customer_id( $user->ID );

		$order->update_meta_data( "_dsm_order",      $data[ "order_id" ] );
		$order->update_meta_data( "_dsm_shopkeeper", $user->ID           );

		$msg = [
			"Shopkeeper: {$user->user_login} ({$user->ID})",
			"Order ID: #" . $data[ "order_id" ],
		];

		$order->add_order_note( implode( "\n", $msg ) );

		$order->set_status( "wc-on-hold" );
		$order->save();

		return new WP_REST_Response( [
			"code"     => "dsm_rest_orders_v1_success",
			"link"     => $order->get_checkout_payment_url(),
			"order_id" => $order->get_id(),
		], 200 );
	}
}

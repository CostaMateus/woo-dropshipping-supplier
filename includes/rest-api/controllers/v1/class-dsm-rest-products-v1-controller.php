<?php
/**
 * REST API Products controller
 *
 * Handles requests to the /products endpoint.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Supplier\RestApi\v1\Controllers
 * @version 	1.0.0
 */

class DSM_REST_Products_V1_Controller extends DSM_REST_Main_Controller
{
	/**
	 * Route base.
	 *
	 * @since   1.0.0
	 * @var 	string
	 */
	protected $rest_base = "products";

	/**
	 * Register the routes for products.
	 *
	 * @since   1.0.0
	 * @return  void
	 */
	public function register_routes()
	{
		register_rest_route(
			$this->namespace,
			"/{$this->rest_base}",
			[
				[
					"methods"             => WP_REST_Server::READABLE,
					"callback"            => [ $this, "get_items"         ],
					"permission_callback" => [ $this, "permissions_check" ],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			"/{$this->rest_base}/imported",
			[
				[
					"methods"             => WP_REST_Server::CREATABLE,
					"callback"            => [ $this, "imported_items"    ],
					"permission_callback" => [ $this, "permissions_check" ],
				],
			]
		);
	}

	/**
	 * Get a collection of WC Products.
	 *
	 * @since 	1.0.0
	 * @param 	WP_REST_Request $request Full details about the request.
	 * @return 	WP_Error|WP_REST_Response
	 */
	public function get_items( $request )
	{
		$checkAuth = $this->checkAuth( $request );

		if ( !$checkAuth[ "success" ] )
		{
			$error = $checkAuth[ "error" ];
			return new WP_Error( $error[ "code" ], $error[ "message" ], $error[ "status" ] );
		}

		/**
		 * Disable permissions check locally
		 */
		add_filter( "woocommerce_rest_check_permissions", "__return_true" );

		$page      = 1;
		$data      = [];
		$request   = new WP_REST_Request( "GET", "/wc/v3/products" );

		do
		{
			/**
			 * Load all products at once to avoid pagination issues
			 */
			$request->set_query_params( [
				"per_page" => 100,
				"page"     => $page++,
				"type"     => "simple",
				"status"   => "publish"
			] );

			$response = rest_do_request( $request );
			$aux      = rest_get_server()->response_to_data( $response, false );

			$data     = array_merge( $data, $aux );
		}
		while ( count( $aux ) == 100 );

		return new WP_REST_Response( [
			"code"  => "dsm_rest_products_v1_success",
			"count" => count( $data ),
			"data"  => $data
		], 200 );
	}

	/**
	 * Saves in the product which shopkeeper imported it
	 *
	 * @since   1.0.0
	 * @param  	WP_REST_Request 	$request Full details about the request.
	 * @return 	WP_Error|WP_REST_Response
	 */
	public function imported_items( $request )
	{
		$checkAuth = $this->checkAuth( $request );

		if ( !$checkAuth[ "success" ] )
		{
			$error = $checkAuth[ "error" ];
			return new WP_Error( $error[ "code" ], $error[ "message" ], $error[ "status" ] );
		}

		$user      = $checkAuth[ "user" ];
		$data      = json_decode( $request->get_body(), true );

		if ( !isset( $data[ "skus" ] ) )
			return new WP_Error( "dsm_rest_products_v1_missing_data", __( "Missing data body.", "woo_ds_supplier" ), 400 );

		$skus      = $data[ "skus" ];

		foreach ( $skus as $sku )
		{
			$product_id = wc_get_product_id_by_sku( $sku );

			if ( $product_id )
			{
				$product    = wc_get_product( $product_id );

				$meta       = $user->ID;

				if ( $product->meta_exists( "_dsm_shopkeepers_imported" ) )
				{
					$meta   = $product->get_meta( "_dsm_shopkeepers_imported", true );
					$meta   = explode( ",", $meta );
					$meta[] = $user->ID;
					$meta   = array_unique( $meta );
					$meta   = implode( ",", $meta );
				}

				$product->update_meta_data( "_dsm_shopkeepers_imported", $meta );
				$product->save_meta_data();
			}
		}

		return new WP_REST_Response( [
			"code" => "dsm_rest_products_v1_success",
		], 200 );
	}
}

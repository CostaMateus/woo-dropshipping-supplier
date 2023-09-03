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

class DSM_REST_Products_V1_Controller extends \WP_REST_Controller
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

	/**
	 * Check if a given request has access to read items.
	 *
	 * @since 	1.0.0
	 * @param  	WP_REST_Request 	$request Full details about the request.
	 * @return 	WP_Error|boolean
	 */
	public function permissions_check( $request )
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
	 * Validate the request.
	 *
	 * @since   1.0.0
	 * @param  	WP_REST_Request 	$request Full details about the request.
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
			$code    = "dsm_rest_products_v1_invalid_email";
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
			$code    = "dsm_rest_products_v1_invalid_secret";
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

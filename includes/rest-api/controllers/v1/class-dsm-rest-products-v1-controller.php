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
					"methods"             => WP_REST_Server::READABLE,
					"callback"            => [ $this, "get_items"                     ],
					"permission_callback" => [ $this, "get_items_permissions_check"   ],
					"args"                => $this->get_collection_params(),
				], [
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
	 * Get the query params for collections of attachments.
	 *
	 * @since   1.0.0
	 * @return 	array
	 */
	public function get_collection_params()
	{
		$params[ "slug"           ] = [
			"description"       => __( "Limit result set to products with a specific slug.", "woo_ds_supplier" ),
			"type"              => "string",
			"validate_callback" => "rest_validate_request_arg",
		];
		$params[ "status"         ] = [
			"default"           => "any",
			"description"       => __( "Limit result set to products assigned a specific status.", "woo_ds_supplier" ),
			"type"              => "string",
			"enum"              => array_merge( [ "any", "future" ], array_keys( get_post_statuses() ) ),
			"sanitize_callback" => "sanitize_key",
			"validate_callback" => "rest_validate_request_arg",
		];
		$params[ "type"           ] = [
			"description"       => __( "Limit result set to products assigned a specific type.", "woo_ds_supplier" ),
			"type"              => "string",
			"enum"              => array_keys( wc_get_product_types() ),
			"sanitize_callback" => "sanitize_key",
			"validate_callback" => "rest_validate_request_arg",
		];
		$params[ "category"       ] = [
			"description"       => __( "Limit result set to products assigned a specific category ID.", "woo_ds_supplier" ),
			"type"              => "string",
			"sanitize_callback" => "wp_parse_id_list",
			"validate_callback" => "rest_validate_request_arg",
		];
		$params[ "tag"            ] = [
			"description"       => __( "Limit result set to products assigned a specific tag ID.", "woo_ds_supplier" ),
			"type"              => "string",
			"sanitize_callback" => "wp_parse_id_list",
			"validate_callback" => "rest_validate_request_arg",
		];
		$params[ "shipping_class" ] = [
			"description"       => __( "Limit result set to products assigned a specific shipping class ID.", "woo_ds_supplier" ),
			"type"              => "string",
			"sanitize_callback" => "wp_parse_id_list",
			"validate_callback" => "rest_validate_request_arg",
		];
		$params[ "attribute"      ] = [
			"description"       => __( "Limit result set to products with a specific attribute.", "woo_ds_supplier" ),
			"type"              => "string",
			"sanitize_callback" => "sanitize_text_field",
			"validate_callback" => "rest_validate_request_arg",
		];
		$params[ "attribute_term" ] = [
			"description"       => __( "Limit result set to products with a specific attribute term ID (required an assigned attribute).", "woo_ds_supplier" ),
			"type"              => "string",
			"sanitize_callback" => "wp_parse_id_list",
			"validate_callback" => "rest_validate_request_arg",
		];
		$params[ "sku"            ] = [
			"description"       => __( "Limit result set to products with a specific SKU.", "woo_ds_supplier" ),
			"type"              => "string",
			"sanitize_callback" => "sanitize_text_field",
			"validate_callback" => "rest_validate_request_arg",
		];

		return $params;
	}

	/**
	 * Get a collection of posts.
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
	 * Check if a given request has access to read items.
	 *
	 * @since 	1.0.0
	 * @param  	WP_REST_Request $request Full details about the request.
	 * @return 	WP_Error|boolean
	 */
	public function get_items_permissions_check( $request )
	{
		$checkAuth = $this->checkAuth( $request );

		if ( !$checkAuth[ "success" ] )
		{
			$error = $checkAuth[ "error" ];
			return new WP_Error( $error[ "code" ], $error[ "message" ], $error[ "status" ] );
		}

		return true;
	}

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

		return [ "success" => true ];
	}
}

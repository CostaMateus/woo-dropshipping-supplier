<?php
/**
 * REST API Tags controller
 *
 * Handles requests to the /tags endpoint.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Supplier\RestApi\v1\Controllers
 * @version 	1.0.0
 */

class DSM_REST_Tags_V1_Controller extends DSM_REST_Main_Controller
{
	/**
	 * Route base.
	 *
	 * @since   1.0.0
	 * @var 	string
	 */
	protected $rest_base = "tags";

	/**
	 * Register the routes for tags.
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
					"callback"            => [ $this, "save_tag"          ],
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
	public function save_tag( $request )
	{
		$checkAuth  = $this->checkAuth( $request );

		if ( !$checkAuth[ "success" ] )
		{
			$error = $checkAuth[ "error" ];
			return new WP_Error( $error[ "code" ], $error[ "message" ], $error[ "status" ] );
		}

		$data       = json_decode( $request->get_body(), true );

		error_log( print_r( $data, true ) );

		$upload_dir = wp_upload_dir();

		if ( !empty( $upload_dir[ "basedir" ] ) )
		{
			$order_id = $data[ "order_id" ];
			$tag      = base64_decode( $data[ "tag_content" ] );
			$ext      = pathinfo( $data[ "tag_name" ], PATHINFO_EXTENSION );
			$filename = "dsm_tag_{$order_id}.{$ext}";
			$dirname  = $upload_dir[ "basedir" ] . "/dsm_tags";

			if ( !file_exists( $dirname ) ) wp_mkdir_p( $dirname );

			$path     = "{$dirname}/{$filename}";
			$success  = file_put_contents( $path, $tag );

			if ( !$success ) return new WP_Error( "dsm_rest_tags_v1_error", __( "Error saving tag.", "woo_ds_supplier" ), 400 );

			$order    = wc_get_order( $order_id );

			$order->add_meta_data( "_dsm_order_tag_{$order_id}", true );
			$order->save();

			return new WP_REST_Response( [ "code" => "dsm_rest_orders_v1_success" ], 200 );
		}

		return new WP_Error( "dsm_rest_tags_v1_error", __( "Error saving tag.", "woo_ds_supplier" ), 500 );
	}
}

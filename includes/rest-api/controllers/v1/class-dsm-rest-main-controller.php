<?php
/**
 * REST API Main controller
 *
 * Handles requests to the /orders endpoint.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Supplier\RestApi\v1\Controllers
 * @version 	1.0.0
 */

class DSM_REST_Main_Controller extends \WP_REST_Controller
{
	/**
	 * Endpoint namespace.
	 *
	 * @since   1.0.0
	 * @var 	string
	 */
	protected $namespace = "dsm/v1";

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
	public function checkAuth( $request )
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
			$code    = "dsm_rest_main_v1_invalid_email";
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
			$code    = "dsm_rest_main_v1_invalid_secret";
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

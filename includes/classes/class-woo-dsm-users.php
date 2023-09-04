<?php
/**
 * Users class.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Supplier\Classes
 * @version 	1.0.0
 */

namespace Dropshipping_Supplier\Classes;

class DSM_Users
{
    /**
     * Constructor
     *
     * @since   1.0.0
     * @return  void
     */
    public function __construct()
    {
		add_action( "show_user_profile",          [ $this, "extra_profile_fields"      ], 12    );
		add_action( "edit_user_profile",          [ $this, "extra_profile_fields"      ], 12    );
		add_action( "personal_options_update",    [ $this, "save_extra_profile_fields" ]        );
		add_action( "edit_user_profile_update",   [ $this, "save_extra_profile_fields" ]        );
		add_action( "wp_ajax_generate_secret",    [ $this, "generate_secret"           ]        );

		add_filter( "manage_users_columns",       [ $this, "add_column_title_user"     ]        );
		add_filter( "manage_users_custom_column", [ $this, "add_column_content_user"   ], 10, 3 );
    }

	/**
	 * Add extra fields to shopkeeper profile
	 *
	 * @since 	1.0.0
	 * @param 	object $user
	 * @return 	void
	 */
	public function extra_profile_fields( $user )
	{
		if ( in_array( "shopkeeper", $user->roles ) )
			require_once( dirname( \Dropshipping_Supplier::FILE ) . "/templates/admin/shopkeeper-profile.php" );
	}

	/**
	 * Save change on extra fields of shopkeeper profile
	 *
	 * @since 	1.0.0
	 * @param 	string $user_id
	 * @return 	void
	 */
	public function save_extra_profile_fields( $user_id )
	{
		$empty  = empty( $_POST[ "_wpnonce" ] );
		$verify = !wp_verify_nonce( $_POST[ "_wpnonce" ], "update-user_" . $user_id );

		if ( $empty || $verify ) return;

		if ( !current_user_can( "edit_user", $user_id ) ) return false;

		// if ( isset( $_POST[ "woo_ds_sku_prefix" ] ) && !empty( $_POST[ "woo_ds_sku_prefix" ] ) )
		// 	update_user_meta( $user_id, "_dsm_sku_prefix", $_POST[ "woo_ds_sku_prefix" ] );

		if ( isset( $_POST[ "woo_ds_website" ] ) && !empty( $_POST[ "woo_ds_website" ] ) )
			update_user_meta( $user_id, "_dsm_url", $_POST[ "woo_ds_website" ] );

		if ( isset( $_POST[ "woo_ds_external" ] ) && !empty( $_POST[ "woo_ds_external" ] ) )
			update_user_meta( $user_id, "_dsm_secret_s", $_POST[ "woo_ds_external" ] );
	}

	/**
	 * Generate secret for shopkeeper
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	public function generate_secret()
	{
		$user_id = $_POST[ "id" ];

		if ( !$user_id )
		{
			$message = translate( "Invalid shopkeeper's ID.", "woo_ds_supplier" );
			return wp_send_json_error( $message, 400 );
		}

		$url_sp  = get_user_meta( $user_id, "_dsm_url", true );

		if ( !$url_sp )
		{
			$message = translate( "Invalid shopkeeper's URL. Fill in the 'website' field and save.", "woo_ds_supplier" );
			return wp_send_json_error( $message, 400 );
		}
		else
		{
			$user    = get_user_by( "id", $user_id );

			if ( !in_array( "shopkeeper", $user->roles ) )
			{
				$message = translate( "The current user is not a shopkeeper.", "woo_ds_supplier" );
				return wp_send_json_error( $message, 400 );
			}

			$url     = get_home_url();
			$cost    = get_option( "_dsm_hash_cost" ) ?: 12;
			$pass    = "{$user->ID}|{$user->user_email}|{$url_sp}|{$url}";
			$secret  = password_hash( $pass, PASSWORD_BCRYPT, [ "cost" => $cost ] );

			update_user_meta( $user->ID, "_dsm_secret", $secret );

			return wp_send_json_success( $secret );
		}
	}

	/**
	 * Adds 'DSM Keys' column header to 'Users' page immediately after 'Role' column.
	 *
	 * @since 	1.0.0
	 * @param 	array 	$columns
	 * @return 	array 	$new_columns
	 */
	public function add_column_title_user( $columns )
	{
		if ( isset( $_GET[ "role" ] ) && $_GET[ "role" ] === "shopkeeper" )
		{
			$new_columns = [];

			foreach ( $columns as $column_name => $column_info )
			{
				$new_columns[ $column_name ] = $column_info;
				$name                        = \Dropshipping_Supplier::DOMAIN . "_user_keys";

				if ( "role" === $column_name )
					$new_columns[ $name ] = "DSM Keys";
			}

			return $new_columns;
		}

		return $columns;
	}

	/**
	 * Adds 'DSM Keys' column content to 'Users' page immediately after 'Role' column.
	 *
	 * @since 	1.0.0
	 * @param 	mixed 	$val
	 * @param 	string 	$column
	 * @param 	string 	$user_id
	 * @return 	mixed 	$val
	 */
	public function add_column_content_user( $val, $column, $user_id )
	{
		if ( isset( $_GET[ "role" ] ) && $_GET[ "role" ] === "shopkeeper" )
		{
			$name = \Dropshipping_Supplier::DOMAIN . "_user_keys";

			if ( $name === $column )
			{
				$k1 = get_user_meta( $user_id, "_dsm_secret",   true );
				$k2 = get_user_meta( $user_id, "_dsm_secret_s", true );

				if ( $k1 && $k2 )
				{
					$text  = translate( "Keys setted", "woo_ds_supplier" );
					$class = "status-processing";
				}
				elseif ( ( $k1 && !$k2 ) || ( !$k1 && $k2 ) )
				{
					$text  = translate( "Missing one key", "woo_ds_supplier" );
					$class = "status-on-hold";
				}
				else
				{
					$text  = translate( "Both keys missing", "woo_ds_supplier" );
					$class = "status-failed";
				}

				return "<mark class='order-status {$class}'><span>{$text}</span></mark>";
			}
		}

		return $val;
	}
}

new DSM_Users();
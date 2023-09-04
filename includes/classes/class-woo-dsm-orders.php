<?php
/**
 * Orders class.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Supplier\Classes
 * @version 	1.0.0
 */

namespace Dropshipping_Supplier\Classes;

class DSM_Orders
{
    /**
     * Constructor
     *
     * @since   1.0.0
     * @return  void
     */
    public function __construct()
    {
        add_action( "woocommerce_order_status_changed",      [ $this, "send_order_status_change"      ], 10, 4 );

        add_filter( "wc_order_statuses",                     [ $this, "add_shipped_to_order_statuses" ]        );
		add_filter( "bulk_actions-edit-shop_order",          [ $this, "add_bulk_actions_shop_order"   ], 20, 1 );
		add_filter( "manage_edit-shop_order_columns",        [ $this, "add_column_title_shopkeeper"   ]        );
		add_filter( "manage_shop_order_posts_custom_column", [ $this, "add_column_content_shopkeeper" ], 10, 3 );
    }

    /**
     * Send order status change to shopkeeper
     *
     * @since   1.0.0
     * @param   int     $order_id
     * @param   string  $old_status
     * @param   string  $new_status
     * @param   object  $order
     * @return  void
     */
    public function send_order_status_change( $order_id, $old_status, $new_status, $order )
    {
        $status   = [ "pending", "on-hold", "processing", ];

        if ( in_array( $new_status, $status ) ) return;

        $id       = $order->get_customer_id();
        $url      = get_user_meta( $id, "_dsm_url",      true );
        $secret   = get_user_meta( $id, "_dsm_secret",   true );
        $secret_s = get_user_meta( $id, "_dsm_secret_s", true );

        $email    = get_option( "_dsm_email_general", true );
        $email    = ( !empty( $email ) && is_email( $email ) ) ? $email : get_bloginfo( "admin_email" );

        if ( !$url || !$secret || !$secret_s ) return;

        $skus     = [];

        foreach ( $order->get_items() as $item )
        {
            $product = $item->get_product();
            $skus[]  = $product->get_sku();
        }

        $args     = [
            "timeout"     => 60,
            "sslverify"   => false,
            "data_format" => "body",
            "headers"     => [
                "_dsm_email"      => $email,
                "_dsm_supplier"   => $secret_s,
                "_dsm_shopkeeper" => $secret,
                "Content-Type"    => "application/json",
            ],
            "body"        => wp_json_encode( [
                "order_id"      => $order->get_meta( "_dsm_order", true ),
                "status"        => $new_status,
                "skus"          => $skus,
                "tracking_code" => "123-TESTE"
            ] )
        ];

        $url      = "{$url}/wp-json/dsm/v1/orders/update";

        $response = wp_remote_post( $url, $args );
        $message  = "";

        if ( is_wp_error( $response ) )
        {
            $message = "Failed to inform change of status of order #{$order_id} of shopkeeper ID: {$id};";
            \Dropshipping_Supplier::log( $response->get_error_message(), true );
        }
        else
        {
            $status   = wp_remote_retrieve_response_code( $response );
            $data     = json_decode( $response[ "body" ], true );

            if ( $status != 200 )
            {
                $message = "Failed to inform change of status of order #{$order_id} of shopkeeper ID: {$id};";
                \Dropshipping_Supplier::log( json_encode( $data ), true );
            }
            else
            {
                \Dropshipping_Supplier::log( json_encode( $data ) );
            }
        }

        if ( !empty( $message ) )
        {
            $order->add_order_note( $message );
            $order->save();
        }
    }

	/**
	 * Add 'shipped' status after 'on-hold' status.
	 *
	 * @since 	1.0.0
     * @param   array $order_statuses
	 * @return 	void
	 */
    public function add_shipped_to_order_statuses( $order_statuses )
    {
        $new_order_statuses = [];

        foreach ( $order_statuses as $key => $status )
        {
            $new_order_statuses[ $key ] = $status;

            if ( $key === "wc-on-hold" )
                $new_order_statuses[ "wc-shipped" ] = "Shipped";
        }

        return $new_order_statuses;
    }

	/**
	 * Add option to bulk actions on shop order page, to change status to 'shipped'
	 *
	 * @since 	1.0.0
	 * @param 	array 	$actions
	 * @return 	array
	 */
	public function add_bulk_actions_shop_order( $actions )
	{
		$new_actions = [];

		foreach ( $actions as $key => $action )
		{
			$new_actions[ $key ] = $action;

			if ( "mark_on-hold" === $key )
				$new_actions[ "mark_shipped" ] = __( "Change status to shipped", "woo_dsm_supplier" );
		}

		return $new_actions;
	}

	/**
	 * Adds 'Shopkeeper' column header to 'All Orders' page immediately after 'Status' column.
	 *
	 * @since 	1.0.0
	 * @param 	array 	$columns
	 * @return 	array	$new_columns
	 */
	public function add_column_title_shopkeeper( $columns )
	{
		$new_columns = [];

		foreach ( $columns as $column_name => $column_info )
		{
			$new_columns[ $column_name ] = $column_info;

			if ( "order_status" === $column_name )
				$new_columns[ \Dropshipping_Supplier::DOMAIN . "_shopkeeper" ] = __( "Shopkeeper", "woo_ds_supplier" );
		}

		return $new_columns;
	}

	/**
	 * Adds 'Supplier' column content to 'All Products' page immediately after 'Categories' column.
	 *
	 * @since 	1.0.0
	 * @param 	mixed 	$val
	 * @param 	string 	$column
	 * @param 	string 	$order_id
	 * @return 	mixed 	$val
	 */
	public function add_column_content_shopkeeper( $column, $order_id )
	{
		if ( $column === \Dropshipping_Supplier::DOMAIN . "_shopkeeper" )
		{
			$order         = wc_get_order( $order_id );
			$shopkeeper_id = $order->get_customer_id();
            $shopkeeper    = get_user_by( "id", $shopkeeper_id );

            echo $shopkeeper->user_login;
		}
	}
}

new DSM_Orders();
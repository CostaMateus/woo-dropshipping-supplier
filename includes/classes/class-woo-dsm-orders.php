<?php
/**
 * Orders class.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Shopkeeper\Classes
 * @version 	1.0.0
 */

namespace Dropshipping_Shopkeeper\Classes;

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
        add_action( "woocommerce_order_status_changed", [ $this, "order_status_change" ], 10, 4  );
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
    public static function send_order_status_change( $order_id, $old_status, $new_status, $order )
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
                "order_id" => $order->get_meta( "_dsm_order", true ),
                "status"   => $new_status,
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
}

new DSM_Orders();
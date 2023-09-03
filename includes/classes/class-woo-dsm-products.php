<?php
/**
 * Products class.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Shopkeeper\Classes
 * @version 	1.0.0
 */

namespace Dropshipping_Shopkeeper\Classes;

class DSM_Products
{
    /**
     * Constructor
     *
     * @since   1.0.0
     * @return  void
     */
    public function __construct()
    {
        add_action( "woocommerce_before_product_object_save", [ $this, "send_product_changes"  ], 10, 2 );
    }

    /**
     * Send productt changes to shopkeeper
     *
     * @since   1.0.0
     * @param   object  $product
     * @param   object  $data
     * @return  void
     */
    public static function send_product_changes( $product, $data )
    {
        $data = [
            "stock_quantity" => $product->get_stock_quantity(),
            "stock_status"   => $product->get_stock_status(),
            "sku"            => $product->get_sku(),
            "managed"        => $product->get_manage_stock() ? true : false,
            "regular_price"  => $product->get_regular_price(),
            "sale_price"     => $product->get_sale_price(),
        ];

        if ( $product->meta_exists( "_dsm_shopkeepers_imported" ) )
        {
            $shops = $product->get_meta( "_dsm_shopkeepers_imported", true );
            $shops = explode( ",", $shops );

            $email = get_option( "_dsm_email_general", true );
            $email = ( !empty( $email ) && is_email( $email ) ) ? $email : get_bloginfo( "admin_email" );

            foreach ( $shops as $id )
            {
                $url      = get_user_meta( $id, "_dsm_url",      true );
                $secret   = get_user_meta( $id, "_dsm_secret",   true );
                $secret_s = get_user_meta( $id, "_dsm_secret_s", true );

                if ( !$url || !$secret || !$secret_s ) continue;

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
                    "body"        => wp_json_encode( $data )
                ];

                $url      = "{$url}/wp-json/dsm/v1/products/update";

                $response = wp_remote_post( $url, $args );

                if ( is_wp_error( $response ) )
                {
                    \Dropshipping_Supplier::log( $response->get_error_message(), true );
                    continue;
                }

                $status   = wp_remote_retrieve_response_code( $response );
                $data     = json_decode( $response[ "body" ], true );

                if ( $status != 200 )
                {
                    \Dropshipping_Supplier::log( json_encode( $data ), true );
                    continue;
                }

                \Dropshipping_Supplier::log( json_encode( $data ) );
            }
        }
    }
}

new DSM_Products();
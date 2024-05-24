<?php
/**
 * Helper class.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Shopkeeper\Classes\Helpers
 * @version 	1.0.0
 */

namespace Dropshipping_Shopkeeper\Classes\Helpers;

class DSM_Helper
{
    /**
     * Undocumented function
     *
     * @since   1.0.0
     * @param   object   $product
     * @return  array
     */
    public static function translate_product( $product )
    {
        $attributes = [];
        $categories = [];
        $images     = [];

        if ( get_option( "_dsm_categories_general" ) )
        {
            foreach ( $product->categories as $category )
            {
                $cat = get_category_by_slug( $category->slug );

                if ( $cat instanceof \WP_Term )
                {
                    $categories[] = [ "id" => $cat->id ];
                }
                else
                {
                    $cat = wp_insert_term( $category->name, "product_cat", [ "slug" => $category->slug ] );

                    if ( !is_wp_error( $cat ) )
                    {
                        $term         = get_term( $cat[ "term_id" ], "product_cat" );
                        $categories[] = [ "id" => $term->id ];
                    }
                }
            }
        }

        foreach ( $product->images as $img )
        {
            if ( !self::validate_image_url( $img->src ) ) continue;

            $images[] = [
                "src"  => $img->src,
                "name" => $img->name,
                "alt"  => $img->alt
            ];
        }

        foreach ( $product->attributes as $attr )
        {
            $options      = [];

            foreach ( $attr->options as $option )
                $options[] = $option;

            $attributes[] = [
                "name"      => $attr->name,
                "position"  => $attr->position,
                "visible"   => $attr->visible,
                "variation" => $attr->variation,
                "options"   => $options,
            ];
        }

        $data       = [
            "name"              => $product->name,
            "slug"              => $product->slug,
            "type"              => $product->type,
            "status"            => "draft",

            "description"       => $product->description,
            "short_description" => $product->shortDescript,
            "sku"               => $product->sku,

            "regular_price"     => $product->regular_price,
            "sale_price"        => $product->sale_price,

            "manage_stock"      => $product->manage_stock,
            "stock_quantity"    => $product->stock_quantity,
            "stock_status"      => $product->stock_status,
            "backorders"        => $product->backorders,
            "sold_individually" => $product->sold_individually,

            "weight"            => $product->weight,
            "dimensions"        => [
                "length" => "$product->length",
                "width"  => "$product->width",
                "height" => "$product->height",
            ],

            // "shipping_class"    => "", // TODO validar essa opção

            "upsell_ids"        => [],
            "cross_sell_ids"    => [],
            "parent_id"         => 0,
            "purchase_note"     => "",

            "tags"              => [ [ "id" => 289 ] ],

            "categories"        => $categories,
            "images"            => $images,
            "attributes"        => $attributes,
        ];

        return $data;
    }

    /**
     * Validate image url
     *
     * @since   1.0.0
     * @param   string $url
     * @return  bool
     */
    public static function validate_image_url( $url )
    {
        if ( isset( explode( ".jpg",  $url )[ 1 ] ) )
            return true;

        if ( isset( explode( ".jpeg", $url )[ 1 ] ) )
            return true;

        if ( isset( explode( ".png",  $url )[ 1 ] ) )
            return true;

        return false;
    }

    /**
     * Process regular price - sync
     *
     * @since   1.0.0
     * @param   string|float  $regular
     * @param   string|float  $newPrice
     * @param   string        $status
     * @param   object        $product
     * @return  bool
     */
    public static function process_regular_price( $regular, $newPrice, $status, &$product )
    {
        $change = false;

        if ( $regular != $newPrice )
        {
            $change    = true;
            $newStatus = ( $regular > $newPrice ) ? "down" : "up";

            $product->update_meta_data( "_dsm_supplier_regular_price", $newPrice );
        }
        else
        {
            if ( $status != "even" )
            {
                $change    = true;
                $newStatus = "even";
            }
        }

        if ( $change )
        {
            $log[ "incoming_data" ][ "status_r" ] = $newStatus;

            $product->update_meta_data( "_dsm_supplier_status_regular", $newStatus            );
            $product->update_meta_data( "_dsm_supplier_price_upd",      date( "Y-m-d H:i:s" ) );
        }

        return $change;
    }

    /**
     * Process sale price - sync
     *
     * @since   1.0.0
     * @param   string|float  $regular
     * @param   string|float  $sale
     * @param   string        $newPrice
     * @param   string        $status
     * @param   object        $product
     * @return  bool
     */
    public static function process_sale_price( $regular, $sale, $newPrice, $status, &$product )
    {
        $change = false;

        if ( $sale && $newPrice )
        {
            if ( $sale != $newPrice )
            {
                $change    = true;
                $newStatus = ( $sale > $newPrice ) ? "down" : "up";
                $product->update_meta_data( "_dsm_supplier_sale_price", $newPrice );
            }
            else
            {
                if ( $status != "even" )
                {
                    $change    = true;
                    $newStatus = "even";
                }
            }
        }
        elseif ( $sale && !$newPrice )
        {
            $change    = true;
            $newStatus = "even";
            $product->update_meta_data( "_dsm_supplier_sale_price", null );
        }
        elseif ( !$sale && $newPrice )
        {
            $change    = true;
            $newStatus = ( $regular > $newPrice ) ? "down" : "up";

            $product->update_meta_data( "_dsm_supplier_sale_price", $newPrice );
        }

        if ( $change )
        {
            $log[ "incoming_data" ][ "status_s" ] = $newStatus;

            $product->update_meta_data( "_dsm_supplier_status_sale", $newStatus            );
            $product->update_meta_data( "_dsm_supplier_price_upd",   date( "Y-m-d H:i:s" ) );
        }

        return $change;
    }
}

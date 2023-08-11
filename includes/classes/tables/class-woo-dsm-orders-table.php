<?php
/**
 * Orders table class.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Supplier\Classes\Tables
 * @version 	1.0.0
 */

namespace Dropshipping_Supplier\Classes\Tables;

class DSM_Orders_table extends \WP_List_Table
{
    /**
     * WC Orders
     *
     * @var array|stdClass
     */
    protected $orders;

    /**
     * Undocumented function
     *
     * @since   1.0.0
     * @return  void
     */
	// public function __construct()
    // {
    //     parent::__construct();

    //     // $this->products = wc_get_products( [
    //     //     "limit"        => -1,
    //     //     "meta_key"     => "_dsm_supplier",
    //     //     "meta_compare" => "EXISTS",
    //     // ] );

	// 	// $users          = get_users( [
	// 	// 	"role"  => "supplier",
	// 	// 	"order" => "ASC"
	// 	// ] );

    //     // foreach ( $this->products as &$product )
    //     // {
    //     //     foreach ( $users as $user )
    //     //     {
    //     //         if ( $user->ID == $product->get_meta( "_dsm_supplier" ) )
    //     //         {
    //     //             $product->supplier = "{$user->user_firstname} {$user->user_lastname}";
    //     //             break;
    //     //         }
    //     //     }
    //     // }
    // }

    /**
     * Define the columns that are going to be used in the table
     *
     * @since   1.0.0
     * @return  array   $columns    The array of columns to use with the table
     */
    // public function get_columns()
    // {
    //     return $columns = [
    //         // "cb"       => "",
    //         "thumbnail"  => __( "Thumbnail",  "woo_ds_shopkeeper" ),
    //         "name"       => __( "Name",       "woo_ds_shopkeeper" ),
    //         "supplier"   => __( "Supplier",   "woo_ds_shopkeeper" ),
    //         "sku"        => __( "SKU",        "woo_ds_shopkeeper" ),
    //         "stock"      => __( "Stock",      "woo_ds_shopkeeper" ),
    //         "price"      => __( "Price",      "woo_ds_shopkeeper" ),
    //         "categories" => __( "Categories", "woo_ds_shopkeeper" ),
    //         "date"       => __( "Date",       "woo_ds_shopkeeper" ),
    //     ];
    // }

    /**
     * Decide which columns to activate the sorting functionality on
     *
     * @since   1.0.0
     * @return  array   $sortable     The array of columns that can be sorted by the user
     */
    // public function get_sortable_columns()
    // {
    //     return $sortable = [
    //         "name"     => [ "name",     false ],
    //         "supplier" => [ "supplier", false ],
    //         "sku"      => [ "sku",      false ],
    //         "price"    => [ "price",    false ],
    //         "date"     => [ "date",     false ],
    //     ];
    // }

    /**
     * Sort users by name, supplier, sku, price or date.
     *
     * @since   1.0.0
     * @param   object $a
     * @param   object $b
     * @return  int
     */
    // public function usort_reorder( $a, $b )
    // {
    //     $orderby = "name";

    //     if ( !empty( $_GET[ "orderby" ] ) )
    //     {
    //         if ( $_GET[ "orderby" ] === "supplier" ) $orderby = "supplier";
    //         if ( $_GET[ "orderby" ] === "sku"      ) $orderby = "sku";
    //         if ( $_GET[ "orderby" ] === "price"    ) $orderby = "price";
    //         if ( $_GET[ "orderby" ] === "date"     ) $orderby = "date_created";
    //     }

    //     $order   = ( !empty( $_GET[ "order" ] ) ) ? $_GET[ "order" ] : "asc";
    //     $result  = strcasecmp( $a->$orderby, $b->$orderby );

    //     return ( $order === "asc" ) ? $result : -$result;
    // }

    /**
     *
     * @since   1.0.0
     * @return  void
     */
    // public function get_bulk_actions()
    // {
    //     // $actions = [
    //     //     "delete" => "Delete"
    //     // ];
    //     //
    //     // return $actions;
    // }

    /**
     * No items to show
     *
     * @since   1.0.0
     * @return  void
     */
    // public function no_items()
    // {
    //     _e( "No products found.", "woo_ds_shopkeeper" );
    // }

    /**
     * Prepare the table with different parameters, pagination, columns and table elements
     *
     * @since   1.0.0
     * @return  void
     */
    // public function prepare_items()
    // {
    //     $hidden                = [];
    //     $columns               = $this->get_columns();
    //     $sortable              = $this->get_sortable_columns();

    //     $this->_column_headers = [ $columns, $hidden, $sortable ];

    //     $this->filter_products();

    //     usort( $this->products, [ &$this, "usort_reorder" ] );

    //     $per_page              = 10;
    //     $current_page          = $this->get_pagenum();
    //     $total_items           = count( $this->products );
    //     $total_pages           = ceil( $total_items / $per_page );

    //     $this->products        = array_chunk( $this->products, $per_page )[ $current_page - 1 ];

    //     if ( empty( $paged ) || !is_numeric( $paged ) || $paged<=0 ) $paged = 1;

    //     $this->set_pagination_args( [
    //         "total_items" => $total_items,
    //         "total_pages" => $total_pages,
    //         "per_page"    => $per_page,
    //     ] );

    //     $this->items           = $this->products;
    // }

    /**
     * Unmapped columns
     *
     * @since   1.0.0
     * @param   array|object    $item
     * @param   string          $column_name
     * @return  mixed
     */
    // public function column_default( $item, $column_name )
    // {
    //     switch( $column_name )
    //     {
    //         default:
    //             return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
    //         break;
    //     }
    // }

    /**
     * Checkbox column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    // public function column_cb( $item )
    // {
    //     // return sprintf( "<input type='checkbox' name='users[]' value='%s' />", $item->ID );
    // }

    /**
     * Thumbnail column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    // public function column_thumbnail( $item )
    // {
    //     return wp_get_attachment_image( $item->get_image_id(), [ 40, 40 ], true );
    // }

    /**
     * Name column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    // public function column_name( $item )
    // {
    //     $actions         = [];
    //     $actions[ "id" ] = "ID: {$item->get_id()}";

    //     $product_name    = $item->get_name();

    //     if ( current_user_can( "edit_posts" ) )
    //     {
    //         $edit_link = esc_url(
    //             add_query_arg(
    //                 "wp_http_referer",
    //                 urlencode( wp_unslash( $_SERVER[ "REQUEST_URI" ] ) ),
    //                 get_edit_post_link( $item->get_id() )
    //             )
    //         );

    //         $actions[ "edit" ] = "<a href='{$edit_link}'>" . __( "Edit",   "woo_ds_shopkeeper" ) . "</a>";
    //         $product_name      = "<strong><a href='{$edit_link}'>{$item->get_name()}</a></strong>";
    //     }

    //     return sprintf( "%1s %2s", $product_name, $this->row_actions( $actions ) );
    // }

    /**
     * Supplier column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    // public function column_supplier( $item )
    // {
    //     $id              = $item->get_meta( "_dsm_supplier" );
    //     $actions         = [];
    //     $actions[ "id" ] = "ID: {$id}";
    //     $name            = $item->supplier;

    //     if ( current_user_can( "edit_posts" ) )
    //     {
    //         $edit_link = esc_url(
    //             add_query_arg(
    //                 "wp_http_referer",
    //                 urlencode( wp_unslash( $_SERVER[ "REQUEST_URI" ] ) ),
    //                 get_edit_user_link( $id )
    //             )
    //         );

    //         $actions[ "edit" ] = "<a href='{$edit_link}'>" . __( "Edit",   "woo_ds_shopkeeper" ) . "</a>";
    //         $name              = "<strong><a href='{$edit_link}'>{$item->supplier}</a></strong>";
    //     }

    //     return sprintf( "%1s %2s", $name, $this->row_actions( $actions ) );
    // }

    /**
     * SKU column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    // public function column_sku( $item )
    // {
    //     return $item->get_sku();
    // }

    /**
     * Stock column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    // public function column_stock( $item )
    // {
    //     $stock        = "";
    //     $class        = "";

    //     $stock_status = $item->get_stock_status();
    //     $can_manage   = $item->get_manage_stock();

    //     switch ( $stock_status )
    //     {
    //         case "instock":
    //             $stock = __( "In stock",     "woo_ds_shopkeeper" );
    //             $class = "instock";
    //         break;

    //         case "outofstock":
    //             $stock = __( "Out of stock", "woo_ds_shopkeeper" );
    //             $class = "outofstock";
    //         break;

    //         case "onbackorder":
    //             $stock = __( "On backorder", "woo_ds_shopkeeper" );
    //             $class = "onbackorder";
    //         break;
    //     }

    //     $text = "<mark class='{$class}'>{$stock}</mark>";

    //     if ( $can_manage )
    //     {
    //         $qtty = $item->get_stock_quantity();
    //         $text = "<mark class='{$class}'>{$stock}</mark> ({$qtty})";
    //     }

    //     return $text;
    // }

    /**
     * Price column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    // public function column_price( $item )
    // {
    //     return $item->get_price_html();
    // }

    /**
     * Categories column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    // public function column_categories( $item )
    // {
    //     return $item->get_categories( ", ", " " . _n( " ", " ", sizeof( get_the_terms( $item->get_id(), "product_cat" ) ), "woocommerce" ) . " ", " " );
    // }

    /**
     * Date column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    // public function column_date( $item )
    // {
    //     $status = [
    //         "publish" => __( "Published",      "woo_ds_shopkeeper" ),
    //         "pending" => __( "Pending review", "woo_ds_shopkeeper" ),
    //         "draft"   => __( "Draft",          "woo_ds_shopkeeper" ),
    //         "private" => __( "Private",        "woo_ds_shopkeeper" ),
    //     ];

    //     $status = isset( $status[ $item->get_status() ] ) ? $status[ $item->get_status() ] : ucfirst( $item->get_status() ) ;

    //     $date   = $item->get_date_created();
    //     $date   = wc_format_datetime( $date, "Y/m/d H:i" );

    //     return $status . "<br>" . $date;
    // }

    /**
     *
     * @since   1.0.0
     * @return  void
     */
    // public function extra_tablenav( $which )
    // {
    //     // if ( $which == "top" )
    //     //     echo"Hello, I'm before the table";

    //     // if ( $which == "bottom" )
    //     //     echo"Hi, I'm after the table";
    // }

    /**
     * Filter products
     *
     * @since   1.0.0
     * @return  void
     */
    // public function filter_products()
    // {
    //     if ( isset( $_GET[ "supplier" ] ) )
    //     {
    //         $products = [];

    //         foreach ( $this->products as $product )
    //             if ( $_GET[ "supplier" ] == $product->get_meta( "_dsm_supplier" ) )
    //                 $products[] = $product;

    //         $this->products = $products;
    //     }
    // }
}

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
     * Orders data
     *
     * @since   1.0.0
     * @var     array|stdClass
     */
    protected $orders;

    /**
     * Users data
     *
     * @since   1.0.0
     * @var     array|stdClass
     */
    protected $users;

    /**
     * Undocumented function
     *
     * @since   1.0.0
     * @return  void
     */
	public function __construct()
    {
        parent::__construct();

        $this->load_users();
        $this->load_orders();
    }

    /**
     * Load the users data
     *
     * @since   1.0.0
     * @return  void
     */
    private function load_users()
    {
		$this->users = get_users( [
			"role"  => "shopkeeper",
			"order" => "ASC"
		] );
    }

    /**
     * Load the products data
     *
     * @since   1.0.0
     * @return  void
     */
    private function load_orders()
    {
        $this->orders = wc_get_orders( [
            "limit"        => -1,
            "meta_key"     => "_dsm_shopkeeper",
            "meta_compare" => "EXISTS",
        ] );

        foreach ( $this->orders as &$order )
        {
            foreach ( $this->users as $user )
            {
                if ( $user->ID == $order->get_meta( "_dsm_shopkeeper" ) )
                {
                    $order->shopkeeper = $user->user_login;
                    break;
                }
            }
        }
    }

    /**
     * Define the columns that are going to be used in the table
     *
     * @since   1.0.0
     * @return  array   $columns    The array of columns to use with the table
     */
    public function get_columns()
    {
        return $columns = [
            "cb"         => "",
            "order_id"   => __( "Order ID",   "woo_ds_supplier" ),
            "shopkeeper" => __( "Shopkeeper", "woo_ds_supplier" ),
            "date"       => __( "Date",       "woo_ds_supplier" ),
            "status"     => __( "Status",     "woo_ds_supplier" ),
            "total"      => __( "Total",      "woo_ds_supplier" ),
        ];
    }

    /**
     * Decide which columns to activate the sorting functionality on
     *
     * @since   1.0.0
     * @return  array   $sortable     The array of columns that can be sorted by the user
     */
    public function get_sortable_columns()
    {
        return $sortable = [
            "order_id"   => [ "order_id",   false ],
            "shopkeeper" => [ "shopkeeper", false ],
            "date"       => [ "date",       false ],
            "status"     => [ "status",     false ],
            "total"      => [ "total",      false ],
        ];
    }

    /**
     * Sort users by order_id, shopkeeper, date, status or total
     *
     * @since   1.0.0
     * @param   object $a
     * @param   object $b
     * @return  int
     */
    public function usort_reorder( $a, $b )
    {
        $order   = ( !empty( $_GET[ "order"   ] ) ) ? $_GET[ "order"   ] : "desc";
        $orderby = ( !empty( $_GET[ "orderby" ] ) ) ? $_GET[ "orderby" ] : "order_id";

        if ( $_GET[ "orderby" ] === "order_id"   ) $orderby = "order_id";
        if ( $_GET[ "orderby" ] === "shopkeeper" ) $orderby = "shopkeeper";
        if ( $_GET[ "orderby" ] === "date"       ) $orderby = "date";
        if ( $_GET[ "orderby" ] === "status"     ) $orderby = "status";
        if ( $_GET[ "orderby" ] === "total"      ) $orderby = "total";

        if ( $orderby == "order_id" )
        {
            $result = strcasecmp( $a->get_id(), $b->get_id() );

            if ( $result !== 0 )
                return ( $order === "asc" ) ? $result : -$result;
        }

        if ( $orderby == "shopkeeper" )
        {
            $result = strcasecmp( $a->shopkeeper, $b->shopkeeper );

            if ( $result !== 0 )
                return ( $order === "asc" ) ? $result : -$result;
        }

        if ( $orderby == "date" )
        {
            $ta = strtotime( $a->get_date_created() );
            $tb = strtotime( $b->get_date_created() );

            if ( strcasecmp( $ta, $tb ) !== 0 )
            {
                if ( $order === "asc" )
                    return $ta - $tb;
                else
                    return $tb - $ta;
            }
        }

        if ( $orderby == "status" )
        {
            $result = strcasecmp( $a->get_status(), $b->get_status() );

            if ( $result !== 0 )
                return ( $order === "asc" ) ? $result : -$result;
        }

        if ( $orderby == "total" )
        {
            $ta = $a->get_total();
            $tb = $b->get_total();

            if ( strcasecmp( $ta, $tb ) !== 0 )
            {
                if ( $order === "asc" )
                    return $ta - $tb;
                else
                    return $tb - $ta;
            }
        }
    }

    /**
     * Get bulk actions
     *
     * @since   1.0.0
     * @return  array
     */
    public function get_bulk_actions()
    {
        $actions = [
            "upd_shipped" =>  __( "Set status shipped", "woo_ds_supplier" )
        ];

        return $actions;
    }

    /**
     * No items to show
     *
     * @since   1.0.0
     * @return  void
     */
    public function no_items()
    {
        _e( "No products found.", "woo_ds_supplier" );
    }

    /**
     * Prepare the table with different parameters, pagination, columns and table elements
     *
     * @since   1.0.0
     * @return  void
     */
    public function prepare_items()
    {
        $this->process_bulk_action();

        $hidden                = [];
        $columns               = $this->get_columns();
        $sortable              = $this->get_sortable_columns();

        $this->_column_headers = [ $columns, $hidden, $sortable ];

        $this->filter_orders();

        usort( $this->orders, [ &$this, "usort_reorder" ] );

        $per_page              = 25;
        $current_page          = $this->get_pagenum();
        $total_items           = count( $this->orders );
        $total_pages           = ceil( $total_items / $per_page );

        if ( $total_items >= 1 )
        {
            $this->orders = array_chunk( $this->orders, $per_page )[ $current_page - 1 ];

            if ( empty( $paged ) || !is_numeric( $paged ) || $paged<=0 )
                $paged = 1;

            $this->set_pagination_args( [
                "total_items" => $total_items,
                "total_pages" => $total_pages,
                "per_page"    => $per_page,
            ] );

            $this->items  = $this->orders;
        }
        else
        {
            $this->items  = [];
        }
    }

    /**
     * Process the bulk actions
     *
     * @since   1.0.0
     * @return  void
     */
    public function process_bulk_action()
    {
        if ( isset( $_POST[ "_wpnonce" ] ) && !empty( $_POST[ "_wpnonce" ] ) )
        {
            $nonce  = htmlspecialchars( $_POST[ "_wpnonce" ] );
            $action = "bulk-" . $this->_args[ "plural" ];

            if ( !wp_verify_nonce( $nonce, $action ) ) wp_die( "Nope! Security check failed!" );
        }

        $action  = $this->current_action();
        $actions = array_keys( $this->get_bulk_actions() );

        if ( !in_array( $action, $actions ) ) return;

        switch ( $action )
        {
            case "upd_shipped":
                $this->process_b_a_upd_status( $_POST[ "orders" ] );
            break;
        }
    }

    /**
     * Unmapped columns
     *
     * @since   1.0.0
     * @param   array|object    $item
     * @param   string          $column_name
     * @return  mixed
     */
    public function column_default( $item, $column_name )
    {
        switch( $column_name )
        {
            default:
                return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
            break;
        }
    }

    /**
     * Checkbox column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    public function column_cb( $item )
    {
        return sprintf( "<input type='checkbox' name='orders[]' value='%s' />", $item->ID );
    }

    /**
     * Name column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    public function column_order_id( $item )
    {
        $id = "<strong>#{$item->get_id()}</strong>";

        if ( current_user_can( "edit_posts" ) )
        {
            $edit_link = esc_url(
                add_query_arg(
                    "wp_http_referer",
                    urlencode( wp_unslash( $_SERVER[ "REQUEST_URI" ] ) ),
                    $item->get_edit_order_url()
                )
            );

            $id        = "<strong><a href='{$edit_link}' target='_blank' >{$id}</a></strong>";
        }

        return $id;
    }

    /**
     * Shopkeeper column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    public function column_shopkeeper( $item )
    {
        $id              = $item->get_meta( "_dsm_shopkeeper" );
        $actions         = [];
        $actions[ "id" ] = "ID: {$id}";
        $name            = $item->shopkeeper;

        if ( current_user_can( "edit_posts" ) )
        {
            $edit_link = esc_url(
                add_query_arg(
                    "wp_http_referer",
                    urlencode( wp_unslash( $_SERVER[ "REQUEST_URI" ] ) ),
                    get_edit_user_link( $id )
                )
            );

            $actions[ "edit" ] = "<a href='{$edit_link}' target='_blank' >" . __( "Edit",   "woo_ds_supplier" ) . "</a>";
            $name              = "<strong><a href='{$edit_link}' target='_blank' >{$item->shopkeeper}</a></strong>";
        }

        return sprintf( "%1s %2s", $name, $this->row_actions( $actions ) );
    }

    /**
     * Date column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    public function column_date( $item )
    {
        return wc_format_datetime( $item->get_date_created(), "Y/m/d H:i" );
    }

    /**
     * Status column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    public function column_status( $item )
    {
		if ( in_array( $item->get_status(), array( "trash", "draft", "auto-draft" ), true ) )
        {
			$status_name = ( get_post_status_object( $item->get_status() ) )->label;
		}
        else
        {
			$status_name = wc_get_order_status_name( $item->get_status() );
		}

        $class = esc_attr( sanitize_html_class( 'status-' . $item->get_status() ) );

        return "<mark class='order-status {$class}'><span>{$status_name}</span></mark>";
    }

    /**
     * Total column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    public function column_total( $item )
    {
        return wc_price( $item->get_total() );
    }

    /**
     * Extra infos on top or bottom of the table
     *
     * @since   1.0.0
     * @return  void
     */
    public function extra_tablenav( $which )
    {
        // if ( $which == "top"    )
        // if ( $which == "bottom" )

        require( dirname( \Dropshipping_Supplier::FILE ) . "/templates/notices/message.php" );
    }

    /**
     * Filter orders
     *
     * @since   1.0.0
     * @return  void
     */
    public function filter_orders()
    {
        if ( isset( $_GET[ "shopkeeper" ] ) )
        {
            $orders = [];

            foreach ( $this->orders as $order )
                if ( $_GET[ "supplier" ] == $order->get_meta( "_dsm_shopkeeper" ) )
                    $orders[] = $order;

            $this->orders = $orders;
        }
    }

    /**
     * Update order status.
     *
     * @since   1.0.0
     * @return  void
     */
    public function process_b_a_upd_status( $orders )
    {
        if ( !empty( $orders ) )
        {
            foreach ( $orders as $order_id )
            {
                $order = wc_get_order( $order_id );

                if ( $order->get_status() == "processing" )
                {
                    $order->update_status( "shipped" );
                    $order->save();

                    $log = "INFO - Order #{$order_id} updated via bulk action.";
                    \Dropshipping_Supplier::log( $log );
                }
            }
        }
    }
}

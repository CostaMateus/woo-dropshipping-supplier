<?php
/**
 * Users table class.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Supplier\Classes\Tables
 * @version 	1.0.0
 */

namespace Dropshipping_Supplier\Classes\Tables;

use Dropshipping_Shopkeeper\Classes\Helpers\DSM_Helper;

class DSM_Users_table extends \WP_List_Table
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
     * Constructor
     *
     * @since   1.0.0
     * @return  void
     */
    public function __construct()
    {
        parent::__construct();

        $this->load_orders();
        $this->load_users();
    }

    /**
     * Load the orders data
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

        array_map( function( &$user ) {
            $user->usermeta = array_map( function ($data ) {
                return reset( $data );
            }, get_user_meta( $user->ID ) );
            return $user;
        }, $this->users );

        foreach ( $this->users as &$user )
        {
            $count = 0;

            foreach ( $this->orders as $order )
            {
                $id = $order->get_meta( "_dsm_shopkeeper" );

                if ( !empty( $id ) && $id == $user->ID )
                    $count++;
            }

            $user->qty_orders = $count;
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
            "cb"       => "",
            "username" => __( "Username", "woo_ds_supplier" ),
            "name"     => __( "Name",     "woo_ds_supplier" ),
            "email"    => __( "Email",    "woo_ds_supplier" ),
            "orders"   => __( "Orders",   "woo_ds_supplier" ),
            "keys"     => __( "Keys",     "woo_ds_supplier" ),
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
            "username" => [ "username", false ],
            "email"    => [ "email",    false ],
            "orders"   => [ "orders",   false ],
        ];
    }

    /**
     * Sort users by username, email or quantity of products.
     *
     * @since   1.0.0
     * @param   object $a
     * @param   object $b
     * @return  int
     */
    public function usort_reorder( $a, $b )
    {
        $orderby = "username";

        if ( !empty( $_GET[ "orderby" ] ) )
        {
            if ( $_GET[ "orderby" ] === "email"  ) $orderby = "user_email";
            if ( $_GET[ "orderby" ] === "orders" ) $orderby = "qty_orders";
        }

        $order   = ( !empty( $_GET[ "order" ] ) ) ? $_GET[ "order" ] : "asc";
        $result  = strcasecmp( $a->$orderby, $b->$orderby );

        return ( $order === "asc" ) ? $result : -$result;
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
            // "sync"         => __( "Sync stock",       "woo_ds_supplier" ),
            // "import"       => __( "Import products",  "woo_ds_supplier" ),
            "del_orders" => __( "Delete orders",    "woo_ds_supplier" ),
            "del_users"  => __( "Delete suppliers", "woo_ds_supplier" )
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
        _e( "No users found.", "woo_ds_supplier" );
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

        usort( $this->users, [ &$this, "usort_reorder" ] );

        $per_page              = 25;
        $current_page          = $this->get_pagenum();
        $total_items           = count( $this->users );
        $total_pages           = ceil( $total_items / $per_page );

        $this->users           = array_chunk( $this->users, $per_page )[ $current_page - 1 ];

        if ( empty( $paged ) || !is_numeric( $paged ) || $paged <= 0 )
            $paged = 1;

        $this->set_pagination_args( [
            "total_items" => $total_items,
            "total_pages" => $total_pages,
            "per_page"    => $per_page,
        ] );

        $this->items           = $this->users;
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
            // case "sync":
            //     $this->process_b_a_sync();
            // break;
            //
            // case "import":
            //     $this->process_b_a_import();
            // break;

            case "del_orders":
                $this->process_b_a_del_orders( $_POST[ "users" ] );
            break;

            case "del_users":
                $this->process_b_a_del_users( $_POST[ "users" ] );
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
        $meta   = $item->usermeta;
        $key1   = $meta[ "_dsm_secret"   ];
        $key2   = $meta[ "_dsm_secret_s" ];

        $prefix = ( $key1 && $key2 ) ? "" : "disabled";

        return sprintf( "<input type='checkbox' name='users[]' value='%s' %s />", $item->ID, $prefix );
    }

    /**
     * Username column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    public function column_username( $item )
    {
        $actions         = [];
        $actions[ "id" ] = "ID: {$item->ID}";

        $username        = $item->user_login;

        if ( current_user_can( "edit_user", $item->ID ) )
        {
            $edit_link = esc_url(
                add_query_arg(
                    "wp_http_referer",
                    urlencode( wp_unslash( $_SERVER[ "REQUEST_URI" ] ) ),
                    get_edit_user_link( $item->ID )
                )
            );

            $actions[ "edit" ] = "<a href='{$edit_link}' target='_blank' >" . __( "Edit", "woo_ds_supplier" ) . "</a>";
            $username          = "<strong><a href='{$edit_link}' target='_blank' >{$username}</a></strong>";
        }

        return sprintf( "%1s %2s", $username, $this->row_actions( $actions ) );
    }

    /**
     * Name column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    public function column_name( $item )
    {
        return "{$item->user_firstname} {$item->user_lastname}";
    }

    /**
     * Email column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    public function column_email( $item )
    {
        return $item->user_email;
    }

    /**
     * Keys column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    public function column_keys( $item )
    {
        $meta = $item->usermeta;

        $k1   = $meta[ "_dsm_secret"   ];
        $k2   = $meta[ "_dsm_secret_s" ];

        if ( $k1 && $k2 )
        {
            $text  = __( "Keys setted", "woo_ds_supplier" );
            $class = "status-processing";
        }
        elseif ( ( $k1 && !$k2 ) || ( !$k1 && $k2 ) )
        {
            $text  = __( "Missing one key", "woo_ds_supplier" );
            $class = "status-on-hold";
        }
        else
        {
            $text  = __( "Both keys missing", "woo_ds_supplier" );
            $class = "status-failed";
        }

        return "<mark class='order-status {$class}'><span>{$text}</span></mark>";
    }

    /**
     * Products column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    public function column_orders( $item )
    {
        $link = $item->qty_orders;

        if ( $item->qty_orders > 0 )
        {
            $url  = esc_url( admin_url( "admin.php?page=woo_ds_supplier&tab=orders&shopkeeper={$item->ID}" ) );
            $link = "<a href='{$url}' >{$item->qty_orders}</a>";
        }

        return $link;
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
     * Delete orders of shopkeeper
     *
     * @since   1.0.0
     * @return  void
     */
    public function process_b_a_del_orders( $users )
    {
        if ( !empty( $users ) && !empty( $this->orders ) )
        {
            foreach ( $users as $user_id )
            {
                $count = 0;

                foreach ( $this->orders as $order )
                {
                    $meta_id = $order->get_meta( "_dsm_shopkeeper" ); //Get ID of shopkeeper

                    if ( !empty( $meta_id ) && $meta_id == $user_id )
                    {
                        $order->delete( true );
                        $count++;
                    }
                }

                $log   = "INFO - Shopkeeper ID {$user_id} - deleted {$count} orders";
                \Dropshipping_Supplier::log( $log );
            }
        }
    }

    /**
     * Delete orders and the supplier.
     *
     * @since   1.0.0
     * @return  void
     */
    public function process_b_a_del_users( $users )
    {
        $this->process_b_a_del_orders( $users );

        if ( !empty( $users ) )
        {
            foreach ( $users as $user_id )
            {
                delete_user_meta( $user_id, "_dsm_url"      );
                delete_user_meta( $user_id, "_dsm_secret_s" );

                wp_delete_user( $user_id );

                $log = "INFO - Deleted supplier ID {$user_id}";
                \Dropshipping_Supplier::log( $log );
            }
        }
    }
}

<?php
/**
 * Plugin Name:          #Dropshipping Supplier Module
 * Description:          Dropshipping Supplier Module for WooCommerce.
 * Author:               Mateus Costa
 * Author URI:           https://costamateus.com.br/
 * Version:              1.0.0
 * Text Domain:          woo_ds_supplier
 * RequiresPHP:          7.4
 * RequiresWP:           6.0
 * WC requires at least: 6.3
 * WC tested up to:      6.3
 *
 * @package 			 Dropshipping_Supplier
 * @version 			 1.0.0
 */
defined( "ABSPATH" ) || exit;

register_activation_hook(   __FILE__, [ "Dropshipping_Supplier", "on_activation"   ] );
register_deactivation_hook( __FILE__, [ "Dropshipping_Supplier", "on_deactivation" ] );

add_action( "plugins_loaded", [ "Dropshipping_Supplier", "init" ] );

class Dropshipping_Supplier
{
	/**
	 * Plugin version.
	 *
	 * @since 	1.0.0
	 * @var 	string
	 */
	const VERSION = "1.0.0";

	/**
	 * Plugin domain.
	 *
	 * @since 	1.0.0
	 * @var 	string
	 */
	const DOMAIN  = "woo_ds_supplier";

	/**
	 * Plugin main file.
	 *
	 * @since 	1.0.0
	 * @var 	string
	 */
	const FILE    = __FILE__;

	/**
	 * Instance of this class.
	 *
	 * @since 	1.0.0
	 * @var 	object
	 */
	protected static $instance = null;

	/**
	 * Construct
	 *
	 * @since 	1.0.0
	 */
	private function __construct()
    {
        $has_missing  = false;
        $dependencies = [
            "woo"  => "woocommerce/woocommerce.php",
            "ecfb" => "woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php",
        ];

        foreach ( $dependencies as $key => $dep )
        {
            if ( !in_array( $dep, apply_filters( "active_plugins", get_option( "active_plugins" ) ) ) )
            {
                add_action( "admin_notices", [ $this, "missing_notice_{$key}" ] );
                $has_missing = true;
            }
        }

        if ( $has_missing ) return;

        $this->add_actions();
        $this->add_filters();
    }

	/**
	 * Return an instance of this class.
	 *
	 * @version	1.0.0
	 * @since 	1.0.0
	 * @return 	object A single instance of this class.
	 */
	public static function init()
    {
		if ( self::$instance === null ) self::$instance = new self;

		return self::$instance;
	}

    /**
     * On activation, change all orders with status 'processing' and meta '_woo_ds_supplier_status_shipped' to status 'shipped'.
     *
     * @version	1.0.0
     * @since 	1.0.0
     * @return  void
     */
    public static function on_activation()
    {
        if ( !current_user_can( "activate_plugins" ) ) return;

        self::add_custom_order_status();

        $args   = [
            "limit"        => -1,
            "status"       => [ "processing" ],
            "meta_key"     => "_woo_ds_supplier_status_shipped",
            "meta_compare" => "EXISTS",
        ];

        $orders = wc_get_orders( $args );

        foreach ( $orders as $order )
        {
            $order->update_status( "shipped", "", true );
            $order->save();
        }

        self::add_role();
    }

    /**
     * On deactivation, change all orders with status 'shipped' to 'processing'.
     *
     * @version	1.0.0
     * @since 	1.0.0
     * @return  void
     */
    public static function on_deactivation()
    {
        if ( !current_user_can( "activate_plugins" ) ) return;

        $args   = [
            "limit"  => -1,
            "status" => [ "shipped" ],
        ];

        $orders = wc_get_orders( $args );

        foreach ( $orders as $order )
        {
            $order->update_meta_data( "_woo_ds_supplier_status_shipped", true );
            $order->update_status( "processing" );
            $order->save();
        }

        remove_role( "shopkeeper" );
    }

	/**
	 * Woo missing notice.
	 *
	 * @version	1.0.0
	 * @since 	1.0.0
	 * @return 	void
	 */
	public static function missing_notice_woo()
	{
		require_once( dirname( self::FILE ) . "/templates/notices/missing-woo.php" );
	}

	/**
	 * ECFB missing notice.
	 *
	 * @version	1.0.0
	 * @since 	1.0.0
	 * @return 	void
	 */
	public static function missing_notice_ecfb()
	{
		require_once( dirname( self::FILE ) . "/templates/notices/missing-ecfb.php" );
	}

	/**
	 * Init plugin actions.
	 *
	 * @version	1.0.0
	 * @since 	1.0.0
	 * @return 	void
	 */
    public function add_actions()
    {
        add_action( "init",                  [ $this, "add_custom_order_status" ] );
        add_action( "admin_enqueue_scripts", [ $this, "add_admin_style"         ] );
    }

	/**
	 * Init plugin filters.
	 *
	 * @version	1.0.0
	 * @since 	1.0.0
	 * @return 	void
	 */
    public function add_filters()
    {
        add_filter( "wc_order_statuses", [ $this, "add_shipped_to_order_statuses" ] );
    }

	/**
	 * Create the status 'wc-shipped'.
	 *
	 * @version	1.0.0
	 * @since 	1.0.0
	 * @return 	void
	 */
    public function add_custom_order_status()
    {
        $found    = false;
        $statuses = wc_get_order_statuses();

        foreach ( $statuses as $key => $status )
        {
            if ( $key == "wc-shipped" )
            {
                $found = true;
                break;
            }
        }

        if ( !$found )
        {
            register_post_status( "wc-shipped", [
                "label"                     => "Shipped",
                "public"                    => true,
                "show_in_admin_status_list" => true,
                "show_in_admin_all_list"    => true,
                "exclude_from_search"       => false,
                "label_count"               => _n_noop( "Shipped <span class='count'>(%s)</span>", "Shipped <span class='count'>(%s)</span>" )
            ] );
        }
    }

	/**
	 * Create the role 'shopkeeper'.
	 *
	 * @version	1.0.0
	 * @since 	1.0.0
	 * @return 	void
	 */
    public static function add_role()
    {
        add_role(
            "shopkeeper",
            "Shopkeeper",
            get_role( "shop_manager" )->capabilities,
        );
    }

    /**
     * Add admin style.
     *
	 * @version	1.0.0
	 * @since 	1.0.0
	 * @return 	void
     */
    public function add_admin_style()
    {
		$suffix   = defined( "SCRIPT_DEBUG" ) && SCRIPT_DEBUG ? "" : ".min";
		$css_path = "assets/css/admin/style{$suffix}.css";
		$css_ver  = date( "ymdHis", filemtime( plugin_dir_path( self::FILE ) . $css_path ) );
		wp_enqueue_style( self::DOMAIN . "_admin_css", plugins_url( $css_path, self::FILE ), false, $css_ver, "all" );
    }

	/**
	 * Add 'shipped' status after 'on-hold' status.
	 *
	 * @version	1.0.0
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
}
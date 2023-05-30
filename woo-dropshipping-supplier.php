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
		self::add_hash_cost();
    }

    /**
     * On deactivation, change all orders with status 'shipped' to 'processing'.
     *
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
			$order = ( is_a( $order, "WC_Order_Refund" ) ) ? wc_get_order( $order->get_parent_id() ) : $order;

			if ( !$order ) continue;

			$order->update_meta_data( "_woo_ds_supplier_status_shipped", true );
			$order->update_status( "processing" );
			$order->save();
        }

        remove_role( "shopkeeper" );
		delete_option( "woo_ds_hash_cost" );
    }

	/**
	 * Woo missing notice.
	 *
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
	 * @since 	1.0.0
	 * @return 	void
	 */
    public function add_actions()
    {
        add_action( "init",                     [ $this, "add_custom_order_status"   ]     );
		add_action( "admin_menu",               [ $this, "add_admin_menu"            ], 9  );
        add_action( "admin_enqueue_scripts",    [ $this, "add_admin_style_script"    ]     );

		add_action( "show_user_profile",        [ $this, "extra_profile_fields"      ], 12 );
		add_action( "edit_user_profile",        [ $this, "extra_profile_fields"      ], 12 );

		add_action( "personal_options_update",  [ $this, "save_extra_profile_fields" ]     );
		add_action( "edit_user_profile_update", [ $this, "save_extra_profile_fields" ]     );

		add_action( "wp_ajax_generate_secret",  [ $this, "generate_secret"           ]     );
    }

	/**
	 * Init plugin filters.
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
    public function add_filters()
    {
        add_filter( "wc_order_statuses",                                    [ $this, "add_shipped_to_order_statuses" ] );
		add_filter( "plugin_action_links_" . plugin_basename( self::FILE ), [ $this, "plugin_action_links"           ] );
    }

	/**
	 * Create the status 'wc-shipped'.
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
    public static function add_custom_order_status()
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
	 * Calculates and saves, the maximum cost for the hash
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	public static function add_hash_cost()
	{
		$t = 0.25; // 250 milliseconds
		$c = 8;

		do
		{
			$c++;
			$s = microtime( true );
			password_hash( "woo_ds_hash_cost", PASSWORD_BCRYPT, [ "cost" => $c ] );
			$e = microtime( true );
		}
		while ( ( $e - $s ) < $t );

		add_option( "woo_ds_hash_cost", $c, "", "no" );
	}

    /**
     * Add admin style.
     *
	 * @since 	1.0.0
	 * @return 	void
     */
    public function add_admin_style_script()
    {
		$suffix   = defined( "SCRIPT_DEBUG" ) && SCRIPT_DEBUG ? "" : ".min";
		$css_path = "assets/css/style{$suffix}.css";
		$css_ver  = date( "ymdHis", filemtime( plugin_dir_path( self::FILE ) . $css_path ) );
		wp_enqueue_style( self::DOMAIN . "_css_js", plugins_url( $css_path, self::FILE ), false, $css_ver, "all" );

		$js_path  = "assets/js/script{$suffix}.js";
		$js_ver   = date( "ymdHis", filemtime( plugin_dir_path( self::FILE ) . $js_path ) );
		wp_enqueue_script( self::DOMAIN . "_css_js", plugins_url( $js_path, self::FILE ), [ "json2", "jquery" ], $js_ver, true );

		wp_localize_script(
			self::DOMAIN . "_css_js",
			self::DOMAIN . "_params",
			[ "ajax_url" => admin_url( "admin-ajax.php" ), ]
		);
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
	 * Action links.
	 *
	 * @since 	1.0.0
	 * @param 	array $links
	 * @return 	array
	 */
	public static function plugin_action_links( $links )
    {
        $url            = esc_url( admin_url( "admin.php?page=" . self::DOMAIN ) );
        $text           = __( "Settings", self::DOMAIN );
		$plugin_links   = [];
		$plugin_links[] = "<a href='{$url}' >{$text}</a>";

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Add submenu on WP Settings menu
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	public function add_admin_menu()
	{
		add_menu_page(
			"Dropshipping Supplier Module",
			"DSM - Supplier",
			'manage_options',
			self::DOMAIN,
			[ $this, "display_admin_menu" ],
			plugins_url( "assets/images/supplier.png", self::FILE ),
            65
		);
	}

	/**
	 * Add settings page
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	public function display_admin_menu()
	{
		if ( !current_user_can( "manage_options" ) ) wp_die( __( "You do not have sufficient permissions to access this page." ) );

		$tabs = [
			"general"     => "General",
			"shopkeepers" => "List of shopkeepers",
			"settings"    => "Settings",
			"tools"       => "Tools",
		];

		$tab  = isset( $_GET[ "tab" ] ) ? $_GET[ "tab" ] : "general";

		if ( isset( $_GET[ "error_message" ] ) )
		{
			add_action( "admin_notices", [ $this, "admin_settings_messages" ] );
			do_action( "admin_notices", $_GET[ "error_message" ] );
		}

		require_once( dirname( self::FILE ) . "/templates/admin/settings.php" );
	}

	/**
	 * Handles errors and displays messages accordingly
	 *
	 * @since 	1.0.0
	 * @param 	string $error_message
	 */
	public function admin_settings_messages( $error_message )
	{
		switch ( $error_message )
		{
			case "1":
				$message       = __( "There was an error adding this setting. Please try again. If this persists, shoot us an email.", self::DOMAIN );
				$err_code      = esc_attr( "woo_ds_supplier-example-setting" );
				$setting_field = "woo_ds_supplier_example_setting";
			break;
		}

		$type = "error";

		add_settings_error( $setting_field, $err_code, $message, $type );
	}

	/**
	 * Add extra fields to shopkeeper profile
	 *
	 * @since 	1.0.0
	 * @param 	object $user
	 * @return 	void
	 */
	public static function extra_profile_fields( $user )
	{
		if ( in_array( "shopkeeper", $user->roles ) )
			require_once( dirname( self::FILE ) . "/templates/admin/shopkeeper-profile.php" );
	}

	/**
	 * Save change on extra fields of shopkeeper profile
	 *
	 * @since 	1.0.0
	 * @param 	string $user_id
	 * @return 	void
	 */
	public static function save_extra_profile_fields( $user_id )
	{
		$empty  = empty( $_POST[ "_wpnonce" ] );
		$verify = !wp_verify_nonce( $_POST[ "_wpnonce" ], "update-user_" . $user_id );

		if ( $empty || $verify ) return;

		if ( !current_user_can( "edit_user", $user_id ) ) return false;

		update_user_meta( $user_id, "_shopkeeper_url", $_POST[ "woo_ds_website" ] );
	}

	/**
	 * Generate secret for shopkeeper
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	public function generate_secret()
	{
		$user_id = $_POST[ "id"  ];

		if ( !$user_id )
		{
			$message = translate( "Invalid shopkeeper's ID.", self::DOMAIN );
			return wp_send_json_error( $message, 400 );
		}

		$url_sp  = get_user_meta( $user_id, "_shopkeeper_url", true );

		if ( !$url_sp )
		{
			$message = translate( "Invalid shopkeeper's URL. Fill in the 'website' field and save.", self::DOMAIN );
			return wp_send_json_error( $message, 400 );
		}

		$user    = get_user_by( "id", $user_id );

		if ( !in_array( "shopkeeper", $user->roles ) )
		{
			$message = translate( "The current user is not a shopkeeper.", self::DOMAIN );
			return wp_send_json_error( $message, 400 );
		}

		$url     = get_home_url();
		$cost    = get_option( "woo_ds_hash_cost" );
		$pass    = "{$user->ID}|{$user->user_email}|{$url_sp}|{$url}";
		$secret  = password_hash( $pass, PASSWORD_BCRYPT, [ "cost" => $cost ] );

		update_user_meta( $user->ID, "_shopkeeper_secret", $secret );

		return wp_send_json_success( $secret );
	}
}
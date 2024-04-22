<?php
/**
 * Plugin Name:          #Dropshipping Supplier Module
 * Description:          Dropshipping Supplier Module for WooCommerce.
 * Author:               Mateus Costa
 * Author URI:           https://costamateus.com.br/
 * Version:              1.1.0
 * Text Domain:          woo_ds_supplier
 * Requires at least:    6.0
 * Requires PHP:         7.3
 * WC requires at least: 7.0
 * WC tested up to:      8.0
 *
 * @package 			 Dropshipping_Supplier
 * @version 			 1.1.0
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

		$this->add_requires();
        $this->add_actions();
        $this->add_filters();
		$this->add_classes();
    }

	/**
	 * Log
	 *
	 * @since 	1.0.0
	 * @param 	string 		$message
	 * @param 	boolean 	$is_error
	 * @return 	void
	 */
	public static function log( $message, $is_error = false )
	{
		$debug = get_option( "_dsm_debug" );
		$log   = ( function_exists( "wc_get_logger" ) ) ? wc_get_logger() : new WC_Logger();

		if ( $debug )
		{
			$log->add( self::DOMAIN, $message );
		}
		else if ( $is_error )
		{
			$log->add( self::DOMAIN, $message );
		}
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

        $args   = [
            "limit"        => -1,
            "status"       => [ "processing" ],
            "meta_key"     => "_dsm_supplier_status_shipped",
            "meta_compare" => "EXISTS",
        ];

        $orders = wc_get_orders( $args );

        foreach ( $orders as $order )
        {
			if ( $order->get_meta( "_dsm_supplier_status_shipped" ) && $order->get_status() == "processing" )
			{
				$order->update_status( "shipped", "", true );
				$order->save();
			}
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
            "limit"        => -1,
            "status"       => [ "shipped" ],
            "meta_key"     => "_dsm_shopkeeper",
            "meta_compare" => "EXISTS",
        ];

        $orders = wc_get_orders( $args );

        foreach ( $orders as $order )
        {
			if ( $order->get_meta( "_dsm_shopkeeper" ) && $order->get_status() == "shipped" )
			{
				$order->update_meta_data( "_dsm_supplier_status_shipped", true );
				$order->update_status( "processing" );
				$order->save();
			}
        }

		self::remove_role_from_users();
        self::remove_role();
		self::remove_hash();
		self::remove_users_meta();
		self::remove_options();
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
	 * Init plugin dependency classes.
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	public function add_requires()
	{
		if ( !class_exists( "WP_List_Table" ) )
			require_once( ABSPATH . "wp-admin/includes/class-wp-list-table.php" );

		add_action( "admin_footer", function () {
			require_once( dirname( self::FILE ) . "/templates/load.php" );
		} );

		require_once( dirname( self::FILE ) . "/includes/rest-api/Utilities/SingletonTrait.php" );
		require_once( dirname( self::FILE ) . "/includes/rest-api/Server.php" );
	}

	/**
	 * Init plugin actions.
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
    public function add_actions()
    {
		add_action( "init",                        [ $this, "load_rest_api"             ]     );
        add_action( "init",                        [ $this, "add_custom_order_status"   ]     );

		add_action( "before_woocommerce_init",     [ $this, "compatibility_with_HPOS"   ]     );

		add_action( "admin_menu",                  [ $this, "add_admin_menu"            ], 9  );
        add_action( "admin_enqueue_scripts",       [ $this, "add_admin_style_script"    ]     );

		add_action( "admin_post_dsm_save_general", [ $this, "save_general_tab_settings" ]     );
    }

	/**
	 * Init plugin filters.
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
    public function add_filters()
    {
		add_filter( "plugin_action_links_" . plugin_basename( self::FILE ), [ $this, "plugin_action_links" ] );
	}

	/**
	 * Init plugin needed classes.
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	public function add_classes()
	{
		require_once( dirname( self::FILE ) . "/includes/classes/helpers/class-woo-dsm-helper.php"      );

		require_once( dirname( self::FILE ) . "/includes/classes/tables/class-woo-dsm-orders-table.php" );
		require_once( dirname( self::FILE ) . "/includes/classes/tables/class-woo-dsm-users-table.php"  );

		require_once( dirname( self::FILE ) . "/includes/classes/class-woo-dsm-orders.php"              );
		require_once( dirname( self::FILE ) . "/includes/classes/class-woo-dsm-products.php"            );
		require_once( dirname( self::FILE ) . "/includes/classes/class-woo-dsm-users.php"               );
	}

	/**
	 * Load REST API.
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	public function load_rest_api()
	{
		\Dropshipping_Supplier\RestApi\Server::instance()->init();
	}

	/**
	 * Create the status 'wc-shipped'.
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
    public static function add_custom_order_status()
    {
		register_post_status( "wc-shipped", [
			"label"                     => _x( "Shipped", "Order status", "woo_ds_supplier" ),
			"public"                    => true,
			"exclude_from_search"       => false,
			"show_in_admin_all_list"    => true,
			"show_in_admin_status_list" => true,
			"label_count"               => _n_noop( "Shipped <span class='count'>(%s)</span>", "Shipped <span class='count'>(%s)</span>" )
		] );
    }

	/**
	 * Compatibility with HPOS
	 *
	 * @since 	1.1.0
	 * @return 	void
	 */
	public static function compatibility_with_HPOS()
	{
		if ( class_exists( AutomatticWooCommerceUtilitiesFeaturesUtil::class ) )
			AutomatticWooCommerceUtilitiesFeaturesUtil::declare_compatibility( "custom_order_tables", self::FILE, true );
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
	 * Remove user from role 'shopkeeper'
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	public static function remove_role_from_users()
	{
		$args  = [
			"role"  => "shopkeeper",
			"order" => "ASC"
		];

		$users = get_users( $args );

		foreach ( $users as $user ) $user->remove_role( "shopkeeper" );
	}

	/**
	 * Delete the role 'shopkeeper'.
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	public static function remove_role()
	{
		$shopkeeper = get_role( "shopkeeper" );
		$caps       = $shopkeeper->capabilities;

		foreach ( $caps as $cap ) $shopkeeper->remove_cap( $cap );
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
			password_hash( "_dsm_hash_cost", PASSWORD_BCRYPT, [ "cost" => $c ] );
			$e = microtime( true );
		}
		while ( ( $e - $s ) < $t );

		add_option( "_dsm_hash_cost", $c, "", "no" );
	}

	/**
	 * Delete option hash cost
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	public static function remove_hash()
	{
		delete_option( "_dsm_hash_cost" );
	}

	/**
	 * Delete users meta url and secret
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	public static function remove_users_meta()
	{
		delete_metadata( "user", 0, "_dsm_url",        "", true );
		delete_metadata( "user", 0, "_dsm_debug",      "", true );
		delete_metadata( "user", 0, "_dsm_secret",     "", true );
		delete_metadata( "user", 0, "_dsm_secret_s",   "", true );
		delete_metadata( "user", 0, "_dsm_sku_prefix", "", true );
	}

	/**
	 * Delete options data
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	public static function remove_options()
	{
		delete_option( "_dsm_email_general"      );
		delete_option( "_dsm_categories_general" );
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
		wp_enqueue_style( "woo_dsm_css_js", plugins_url( $css_path, self::FILE ), false, $css_ver, "all" );

		$js_path  = "assets/js/script{$suffix}.js";
		$js_ver   = date( "ymdHis", filemtime( plugin_dir_path( self::FILE ) . $js_path ) );
		wp_enqueue_script( "woo_dsm_css_js", plugins_url( $js_path, self::FILE ), [ "json2", "jquery" ], $js_ver, true );

		wp_localize_script(
			"woo_dsm_css_js",
			"woo_dsm_params",
			[
				"ajax_url" => admin_url( "admin-ajax.php" ),
				"messages" => [
					"del_users"     => __( "This action will delete the selected shopkeeper user as well as all of its orders. Click OK to confirm this action.", "woo_ds_supplier" ),
					"del_orders"    => __( "This action will delete all orders from the selected shopkeeper. Click OK to confirm this action.", "woo_ds_supplier" ),
					"select_users"  => __( "Please select at least one shopkeeper, for any action.", "woo_ds_supplier" ),
					"select_orders" => __( "Please select at least one order, for any action.", "woo_ds_supplier" ),
				]
			]
		);
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
        $url            = esc_url( admin_url( "admin.php?page=" . "woo_ds_supplier" ) );
        $text           = __( "Settings", "woo_ds_supplier" );
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
			"manage_options",
			self::DOMAIN,
			[ $this, "display_admin_menu" ],
			plugins_url( "assets/images/dsm.png", self::FILE ),
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
			"general"     => __( "General",     "woo_ds_supplier" ),
			"shopkeepers" => __( "Shopkeepers", "woo_ds_supplier" ),
			"orders"      => __( "Orders",      "woo_ds_supplier" ),
			// "settings"    => __( "Settings",    "woo_ds_supplier" ),
			// "tools"       => __( "Tools",       "woo_ds_supplier" ),
		];

		$tab  = isset( $_GET[ "tab" ] ) ? $_GET[ "tab" ] : "general";

		if ( $tab == "general" )
		{
			$date      = date( "Y-m-d" );
			$esc_attr  = esc_attr( self::DOMAIN );
			$sanitize  = sanitize_file_name( wp_hash( self::DOMAIN ) );
			$admin_url = "admin.php?page=wc-status&tab=logs&log_file=$esc_attr-$date-$sanitize.log";
			$link      = esc_url( admin_url( $admin_url ) );
			$text      = "WooCommerce &gt Status &gt; Logs";
			$debug     = "<a href='{$link}' target='_blank' >{$text}</a>";
		}

		if ( isset( $_GET[ "error_message" ] ) || isset( $_GET[ "warning_message" ] ) )
		{
			add_action( "admin_notices", [ $this, "admin_settings_messages" ], 10, 2 );
			do_action( "admin_notices", "error", $_GET[ "data" ] );
		}

		if ( isset( $_GET[ "success_message" ] ) )
		{
			add_action( "admin_notices", [ $this, "admin_settings_messages" ], 10, 2 );
			do_action( "admin_notices", "success", $_GET[ "data" ] );
		}

		require_once( dirname( self::FILE ) . "/templates/admin/settings.php" );
	}

	/**
	 * Handles errors and displays messages accordingly
	 *
	 * @since 	1.0.0
	 * @param 	string 	$type
	 * @param 	array 	$data
	 * @return 	void
	 */
	public function admin_settings_messages( $type, $data )
	{
		foreach ( $data as $user_id => $user_data )
		{
			if ( $user_data[ "errors" ] != 0 )
			{
				switch ( $user_data[ "code" ] )
				{
					case "MISS":   // users_table
						$message = sprintf(
							__( "Missing settings for shoppkeeper %1\$s (%2\$s).", "woo_ds_supplier" ),
							$user_data[ "user" ],
							$user_id
						);
					break;
					case "WRG":   // users_table / wp_remote_get
						$message = __( "Internal failure, try again!", "woo_ds_supplier" );
					break;
					case "DRCV":  // users_table / dsm_rest_cannot_view
						$message = __( "Invalid call, try again!", "woo_ds_supplier" );
					break;
					case "DRPIE": // users_table / dsm_rest_products_v1_invalid_email
						$message = sprintf(
							__( "Account not found at shopkeeper %1\$s (%2\$s).<br>Check with him, if the credentials were properly registered.", "woo_ds_supplier" ),
							$user_data[ "user" ],
							$user_id
						);
					break;
					case "DRPIS": // users_table / dsm_rest_products_v1_invalid_secret
						$message = sprintf(
							__( "Invalid credentials for shopkeeper %1\$s (%2\$s).<br>Check the configured secret keys.", "woo_ds_supplier" ),
							$user_data[ "user" ],
							$user_id
						);
					break;

					default:
						$message = __( "Internal failure!", "woo_ds_supplier" );
					break;
				}

				echo "<div class='notice notice-errror is-dismissible'><p>{$message}</p></div>";
			}
			elseif ( $user_data[ "warnings" ] != 0 )
			{
				// $message = sprintf(
				// 	__( "Could not import/sync %1\$s products from supplier %2\$s (%3\$s)!<br>Check the logs for more details.", "woo_ds_supplier" ),
				// 	$user_data[ "warnings" ],
				// 	$user_data[ "user"     ],
				// 	$user_id
				// );

				// echo "<div class='notice notice-warning is-dismissible'><p>{$message}</p></div>";

				// $count   = array_sum( array_column( $data, "count" ) );

				// if ( $count > 0 )
				// {
				// 	$message = __( "{$count} products successfully imported/synced!", "woo_ds_supplier" );
				// 	echo "<div class='notice notice-success is-dismissible'><p>{$message}</p></div>";
				// }
			}
			else
			{
				// $count   = array_sum( array_column( $data, "count" ) );
				// $message = __( "{$count} products successfully imported/synced!", "woo_ds_supplier" );

				// echo "<div class='notice notice-success is-dismissible'><p>{$message}</p></div>";
			}
		}
	}

	/**
	 * Save general tab settings
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	public static function save_general_tab_settings()
	{
        if ( isset( $_POST[ "_wpnonce" ] ) && !empty( $_POST[ "_wpnonce" ] ) )
        {
            $nonce = htmlspecialchars( $_POST[ "_wpnonce" ] );

            if ( !wp_verify_nonce( $nonce, $_POST[ "action" ] ) )
				wp_die( "Nope! Security check failed!" );
        }

		/**
		 * Update DSM email general
		 */
		if ( isset( $_POST[ "dsm_email_general" ] ) && !empty( $_POST[ "dsm_email_general" ] ) )
			update_option( "_dsm_email_general", $_POST[ "dsm_email_general" ] );

		// /**
		//  * Update DSM categories general
		//  */
		// if ( isset( $_POST[ "dsm_categories_general" ] ) && $_POST[ "dsm_categories_general" ] === "on" )
		// 	update_option( "_dsm_categories_general", true );
		// elseif ( !isset( $_POST[ "dsm_categories_general" ] ) )
		// 	update_option( "_dsm_categories_general", false );

		/**
		 * Update DSM debug general
		 */
		if ( isset( $_POST[ "dsm_debug_general" ] ) && $_POST[ "dsm_debug_general" ] === "on" )
			update_option( "_dsm_debug", true );
		elseif ( !isset( $_POST[ "dsm_debug_general" ] ) )
			update_option( "_dsm_debug", false );

		$url = "page=" . self::DOMAIN;

		if ( isset( $_POST[ "_wp_http_referer" ] ) && !empty( $_POST[ "_wp_http_referer" ] ) )
			$url = explode( "?", $_POST[ "_wp_http_referer" ] )[ 1 ];

		$url = admin_url( "admin.php?{$url}" );

		wp_redirect( $url );
		exit;
	}
}
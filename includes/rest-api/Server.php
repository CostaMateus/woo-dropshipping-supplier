<?php
/**
 * Initialize this version of the REST API.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Supplier\RestApi
 * @version 	1.0.0
 */

namespace Dropshipping_Supplier\RestApi;

defined( "ABSPATH" ) || exit;

use Dropshipping_Supplier\RestApi\Utilities\SingletonTrait;

/**
 * Class responsible for loading the REST API and all REST API namespaces.
 */
class Server
{
	use SingletonTrait;

	/**
	 * REST API namespaces and endpoints.
	 *
     * @since   1.0.0
	 * @var 	array
	 */
	protected $controllers = [];

	/**
	 * Hook into WordPress ready to init the REST API as needed.
	 *
     * @since   1.0.0
	 * @return 	void
	 */
	public function init()
    {
		require_once( dirname( __FILE__ ) . "/Controllers/v1/class-dsm-rest-main-controller.php"        );

		require_once( dirname( __FILE__ ) . "/Controllers/v1/class-dsm-rest-orders-v1-controller.php"   );
		require_once( dirname( __FILE__ ) . "/Controllers/v1/class-dsm-rest-products-v1-controller.php" );
		require_once( dirname( __FILE__ ) . "/Controllers/v1/class-dsm-rest-tags-v1-controller.php"     );

		add_action( "rest_api_init", [ $this, "register_rest_routes" ], 10 );
	}

	/**
	 * Register REST API routes.
	 *
     * @since   1.0.0
	 * @return 	void
	 */
	public function register_rest_routes()
    {
		foreach ( $this->get_rest_namespaces() as $namespace => $controllers )
        {
			foreach ( $controllers as $controller_name => $controller_class )
            {
				$this->controllers[ $namespace ][ $controller_name ] = new $controller_class();
				$this->controllers[ $namespace ][ $controller_name ]->register_routes();
			}
		}
	}

	/**
	 * Get API namespaces - new namespaces should be registered here.
	 *
     * @since   1.0.0
	 * @return 	array List of Namespaces and Main controller classes.
	 */
	protected function get_rest_namespaces()
    {
		return apply_filters(
			"woo_ds_supplier_rest_api_get_rest_namespaces",
			[
				"dsm/v1" => $this->get_v1_controllers(),
			]
		);
	}

	/**
	 * List of controllers in the wc/v1 namespace.
	 *
     * @since   1.0.0
	 * @return 	array
	 */
	protected function get_v1_controllers()
    {
		return [
			"orders"   => "DSM_REST_Orders_V1_Controller",
			"products" => "DSM_REST_Products_V1_Controller",
			"tags"     => "DSM_REST_Tags_V1_Controller",
        ];
	}

	/**
	 * Return the path to the package.
	 *
     * @since   1.0.0
	 * @return 	string
	 */
	public static function get_path()
    {
		return dirname( __DIR__ );
	}
}

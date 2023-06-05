<?php
/**
 * General view
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Supplier\Templates\Admin\Tabs
 * @version 	1.0.0
 */

defined( "ABSPATH" ) || exit;

$url = esc_url( admin_url( "users.php?role=shopkeeper" ) );
?>

<section>
	<h2><?php _e( "List of shopkeepers", "woo_ds_supplier" ); ?></h2>

	<p><a href="<?php echo $url; ?>" ><?php _e( "Click here", "woo_ds_supplier" ); ?></a></p>
</section>
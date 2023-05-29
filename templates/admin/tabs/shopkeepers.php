<?php
/**
 * General view
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Supplier
 * @version 	1.0.0
 */
defined( "ABSPATH" ) || exit;

$url = esc_url( admin_url( "users.php?role=shopkeeper" ) );
?>

<section>
	<h2><?php _e( "List of shopkeepers", Dropshipping_Supplier::DOMAIN ); ?></h2>

	<p><a href="<?php echo $url; ?>" ><?php _e( "Click here", Dropshipping_Supplier::DOMAIN ); ?></a></p>
</section>
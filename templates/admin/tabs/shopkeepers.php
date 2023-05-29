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
	<h2>List of shopkeepers</h2>

	<p>
		To see
		<a href="<?php echo $url; ?>" >
			click here
		</a>
	</p>
</section>
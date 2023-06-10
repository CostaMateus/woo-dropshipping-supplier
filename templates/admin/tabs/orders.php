<?php
/**
 * Orders tab view
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Supplier\Templates\Admin\Tabs
 * @version 	1.0.0
 */

defined( "ABSPATH" ) || exit;

use Dropshipping_Supplier\Classes\Tables\DSM_Orders_table;

$orders_table = new DSM_Orders_table();
?>

<section>
	<h2><?php _e( "Orders", "woo_ds_supplier" ); ?></h2>

	<?php
		$orders_table->prepare_items();
		$orders_table->display();
	?>
</section>
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

$o_table = new DSM_Orders_table();
?>

<section>
	<div class="wrap-dsm" id="" >
		<h2 class="wp-heading-inline" style="display:inline;" >
			<?php _e( "Orders", "woo_ds_supplier" ); ?>
		</h2>

		<p><?php _e( "In development!", "woo_ds_supplier" ); ?></p>

		<form id="dsm-users-list-table-form" method="post" >
			<?php
				$page  = htmlspecialchars( $_POST[ "page" ] );
				$paged = filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT );

				printf( "<input type='hidden' name='page'  value='%s' />", $page  );
				printf( "<input type='hidden' name='paged' value='%d' />", $paged );

				$o_table->prepare_items();
				$o_table->display();
			?>
		</form>
	</div>
</section>

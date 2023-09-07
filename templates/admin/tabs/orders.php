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

		<h3>
			<?php _e( "Instructions:", "woo_ds_supplier" ); ?>
			<a id="dsm-instructions-toggle" class="" href="#" >
				<small>
					<?php _e( "Show/Hide", "woo_ds_supplier" ); ?>
				</small>
			</a>
		</h3>
		<div id="dsm-instructions" class="dsm-d-none" >
			<p>
				<?php _e( "- <strong>Set status shipped</strong>:", "woo_ds_supplier" ); ?>
				<br>
				<?php _e( "(1) Will update all orders with status 'processing' to 'shipped'.", "woo_ds_supplier" ); ?>
			</p>
		</div>

		<form id="dsm-orders-list-table-form" method="post" >
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

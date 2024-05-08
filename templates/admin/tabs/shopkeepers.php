<?php
/**
 * Shopkeepers tab view
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Supplier\Templates\Admin\Tabs
 * @version 	1.0.0
 */

defined( "ABSPATH" ) || exit;

use Dropshipping_Supplier\Classes\Tables\DSM_Users_table;

$u_table = new DSM_Users_table();
$nu_url  = esc_url( admin_url( "user-new.php" ) );
$nu_text = __( "Add New", "woo_ds_supplier" );
?>

<section>
	<div class="wrap-dsm" id="dsm-users-list-table" >
		<h2 class="wp-heading-inline" style="display:inline;" >
			<?php _e( "Shopkeepers", "woo_ds_supplier" ); ?>
		</h2>
		<a class="page-title-action" href="<?php echo $nu_url; ?>" >
			<?php echo $nu_text; ?>
		</a>

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
				<?php _e( "- Configure access keys in the shopkeeper profile. You will generate a unique key for each shopkeeper and you will receive a unique key for each shopkeeper.", "woo_ds_supplier" ); ?>
				<br>
				<?php _e( "- Shopkeepers will only import simple type products. Not importing any other type of products.", "woo_ds_supplier" ); ?>
			</p>
			<p>
				<?php _e( "- <strong>Delete orders</strong>:", "woo_ds_supplier" ); ?>
				<br>
				<?php _e( "(1) Will delete all orders from the selected shopkeeper.", "woo_ds_supplier" ); ?>
				<br>
				<?php _e( "(2) Shopkeeper settings will remained.", "woo_ds_supplier" ); ?>
			</p>
			<p>
				<?php _e( "- <strong>Delete shopkeeper</strong>:", "woo_ds_supplier" ); ?>
				<br>
				<?php _e( "(1) Will delete all orders and all settings for the selected shopkeeper.", "woo_ds_supplier" ); ?>
			</p>
		</div>

		<form id="dsm-users-list-table-form" method="post" >
			<?php
				$page  = htmlspecialchars( $_POST[ "page" ] ?? "" );
				$paged = filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT );

				printf( "<input type='hidden' name='page'  value='%s' />", $page  );
				printf( "<input type='hidden' name='paged' value='%d' />", $paged );

				$u_table->prepare_items();
				$u_table->display();
			?>
		</form>
	</div>
</section>
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
$nu_text = __( "Add New", "woo_ds_shopkeeper" );
?>

<section>
	<div class="wrap" id="dsm-users-list-table" >
		<h2 class="wp-heading-inline" style="display:inline;" >
			<?php _e( "Shopkeepers", "woo_ds_supplier" ); ?>
		</h2>
		<a class="page-title-action" href="<?php echo $new_user; ?>" >
			<?php echo $nu_text; ?>
		</a>

		<form id="dsm-users-list-table-form" method="post" >
			<?php
				$page  = htmlspecialchars( $_POST[ "page" ] );
				$paged = filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT );

				printf( "<input type='hidden' name='page'  value='%s' />", $page  );
				printf( "<input type='hidden' name='paged' value='%d' />", $paged );

				$u_table->prepare_items();
				$u_table->display();
			?>
		</form>
	</div>
</section>
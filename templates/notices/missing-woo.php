<?php
/**
 * Missing WooCommerce notice.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Supplier/Notices
 * @version 	1.0.0
 */
defined( "ABSPATH" ) || exit;

$is_installed = false;

if ( function_exists( "get_plugins" ) )
{
	$all_plugins  = get_plugins();
	$key          = "woocommerce/woocommerce.php";
	$is_installed = array_key_exists( $key, $all_plugins );
}
?>

<div class="error" >
	<p>
		<strong>
			<?php esc_html_e( "#Dropshipping Supplier Module", "woo_ds_supplier" ); ?>
		</strong>
		<?php esc_html_e( "depende da última versão do 'WooCommerce' para funcionar!", "woo_ds_supplier" ); ?>
	</p>

	<?php if ( $is_installed && current_user_can( "install_plugins" ) ) : ?>
		<p>
			<a href="<?php echo esc_url( wp_nonce_url( self_admin_url( "plugins.php?action=activate&plugin=woocommerce/woocommerce.php&plugin_status=active" ), "activate-plugin_woocommerce/woocommerce.php" ) ); ?>" class="button button-primary" >
				<?php esc_html_e( "Ative WooCommerce", "woo_ds_supplier" ); ?>
			</a>
		</p>
	<?php else :
		$url = ( current_user_can( "install_plugins" ) ) ? wp_nonce_url( self_admin_url( "update.php?action=install-plugin&plugin=woocommerce" ), "install-plugin_woocommerce" ) : "http://wordpress.org/plugins/woocommerce";
	?>
		<p>
			<a href="<?php echo esc_url( $url ); ?>" class="button button-primary" >
				<?php esc_html_e( "Instale WooCommerce", "woo_ds_supplier" ); ?>
			</a>
		</p>
	<?php endif; ?>
</div>

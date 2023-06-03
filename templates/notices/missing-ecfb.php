<?php
/**
 * Missing ECFB notice
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
	$key          = "woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php";
	$is_installed = array_key_exists( $key, $all_plugins );
}
?>

<div class="error" >
	<p>
		<strong>
			<?php esc_html_e( "#Dropshipping Supplier Module", "woo_ds_supplier" ); ?>
		</strong>
		<?php esc_html_e( "depende da última versão do 'Extra Checkout Fields for Brazil' para funcionar!", "woo_ds_supplier" ); ?>
	</p>

	<?php if ( $is_installed && current_user_can( "install_plugins" ) ) : ?>
		<p>
			<a href="<?php echo esc_url( wp_nonce_url( self_admin_url( "plugins.php?action=activate&plugin=woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php&plugin_status=active" ), "activate-plugin_woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php" ) ); ?>" class="button button-primary" >
				<?php esc_html_e( "Ative Extra Checkout Fields for Brazil", "woo_ds_supplier" ); ?>
			</a>
		</p>
	<?php else :
		$url = ( current_user_can( "install_plugins" ) ) ? wp_nonce_url( self_admin_url( "update.php?action=install-plugin&plugin=woocommerce-extra-checkout-fields-for-brazil" ), "install-plugin_woocommerce-extra-checkout-fields-for-brazil" ) : "http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/";
	?>
		<p>
			<a href="<?php echo esc_url( $url ); ?>" class="button button-primary" >
				<?php esc_html_e( "Instale Extra Checkout Fields for Brazil", "woo_ds_supplier" ); ?>
			</a>
		</p>
	<?php endif; ?>
</div>

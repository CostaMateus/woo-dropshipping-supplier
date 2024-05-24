<?php
/**
 * General tab view
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Supplier\Templates\Admin\Tabs
 * @version 	1.0.0
 */

defined( "ABSPATH" ) || exit;

$_email = get_option( "_dsm_email_general" );
$_debug = ( !empty( get_option( "_dsm_debug" ) ) ) ? "checked" : "";

?>

<section>
	<div class="wrap-dsm" id="" >
		<h2 class="wp-heading-inline" style="display:inline;" >
			<?php _e( "General", "woo_ds_supplier" ); ?>
		</h2>

		<form id="dsm-general-form" class="dsm-form" method="post" action="<?php echo admin_url( "admin-post.php" ); ?>" >
			<input type="hidden" name="action" value="dsm_save_general" />
			<?php wp_nonce_field( "dsm_save_general" ); ?>

			<table class="form-table" >
				<tbody>
					<tr valign="top" >
						<th scope="row" class="titledesc" >
							<label for="dsm_email_general" >
								<?php _e( "E-mail account", "woo_ds_supplier" ); ?>
							</label>
						</th>
						<td class="forminp" >
							<input id="dsm_email_general" class="input-text regular-input" type="email" name="dsm_email_general" value="<?php echo $_email; ?>" >
							<p class="description" ><?php _e( "The shoppkeer must create an account with this email. And this e-mail will be used for communication between the systems (shopkeeper/supplier). If you leave it blank, the default WP email will be used. Forward it to the shopkeeper.", "woo_ds_supplier" ); ?></p>
							<p class="description" ><?php echo __( "Default: ", "woo_ds_supplier" ) . get_bloginfo( "admin_email" ); ?></p>
						</td>
					</tr>

					<tr valign="top" >
						<th scope="row" class="titledesc" >
							<label for="dsm_debug_general" >
								<?php _e( "Debug Log", "woo_ds_supplier" ); ?>
							</label>
						</th>
						<td class="forminp" >
							<input id="dsm_debug_general" class="input-text regular-input" type="checkbox" name="dsm_debug_general" <?php echo $_debug; ?> >
							<?php _e( "Enable / Disable", "woo_ds_supplier" ); ?>
							<p class="description" ><?php _e( "If enabled, will generate detailed log. If not, it will only generate a crash log.", "woo_ds_supplier" ); ?></p>
							<p class="description" ><?php echo $debug; ?></p>
						</td>
					</tr>
				</tbody>
			</table>

			<p class="submit" style="clear: both;" >
				<input type="submit" class="button-primary" value="<?php _e( "Update settings", "woo_ds_supplier" );?>" ></input>
			</p>
		</form>
	</div>
</section>

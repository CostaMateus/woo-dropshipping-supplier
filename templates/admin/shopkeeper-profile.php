<?php

/**
 * Provide a admin area view for the plugin
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Supplier
 * @version 	1.0.0
 */
defined("ABSPATH") || exit;

// Modulo Supplier
// Id spk | Email spk | URL spk | URL supplier

// Modulo Shopkeeper
// ID supplier | Email supplier | URL supplier | URL skp

?>

<h3><?php _e( "Dropshipping Shopkeeper Settings", "woo_ds_supplier" ); ?></h3>

<table class="form-table" >
    <tr>
        <th>
            <label for="woo_ds_website" >
                <?php _e( "Website", "woo_ds_supplier" ); ?>
            </label>
        </th>
        <td>
            <input type="text" name="woo_ds_website" id="woo_ds_website" value="<?php echo esc_attr( get_the_author_meta( "_shopkeeper_url", $user->ID ) ); ?>" class="regular-text" />
            <br>
            <span class="description" ><?php _e( "Please enter the full URL of the shopkeeper's website.", "woo_ds_supplier" ); ?></span>
        </td>
    </tr>
    <tr>
        <th><label for="woo_ds_key" ><?php _e( "Secret key" ); ?></label></th>
        <td>
            <button id="set_new_key" type="button" class="button" aria-expanded="true" >
                <?php _e( "Set new secret key", "woo_ds_supplier" ); ?>
                <span class="dashicons dashicons-update spin" ></span>
            </button>
            <br>
            <span class="description" >
                <?php _e( "Make sure to copy and sent to the shopkeeper new secret key now as will be hidden once you leave this page.", "woo_ds_supplier" ); ?>
            </span>
            <span id="set_key_error" class="description" ></span>

            <div id="new_key" style="padding-top:10px; display:none;" >
                <span class="password-input-wrapper" >
                    <input type="text" name="woo_ds_new_key" id="woo_ds_new_key" class="regular-text empty strong" value="" spellcheck="false" >
                </span>
                <button id="woo_ds_copy" type="button" class="button" data-toggle="0" aria-label="Hide password" >
                    <span class="text" ><?php _e( "Copy", "woo_ds_supplier" ); ?></span>
                </button>
            </div>
            <div id="key_copied" style="display:none;" >
                <span class="description" ><?php _e( "Secret key copied to clipboard.", "woo_ds_supplier" ); ?></span>
            </div>

            <?php if ( get_the_author_meta( "_shopkeeper_secret", $user->ID ) ) : ?>
                <span id="key_set" class="description" ><?php _e( "This user already have a secret key set.", "woo_ds_supplier" ); ?></span>
            <?php endif; ?>
        </td>
    </tr>
</table>
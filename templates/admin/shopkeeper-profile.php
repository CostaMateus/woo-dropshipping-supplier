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
?>

<h3><?php _e( "Extra profile information", "blank" ); ?></h3>

<table class="form-table" >
    <tr>
        <th><label for="address" ><?php _e( "Address" ); ?></label></th>
        <td>
            <input type="text" name="address" id="address" value="<?php echo esc_attr( get_the_author_meta( 'address', $user->ID)); ?>" class="regular-text" /><br />
            <span class="description" ><?php _e( "Please enter your address." ); ?></span>
        </td>
    </tr>
    <tr>
        <th><label for="city" ><?php _e( "City" ); ?></label></th>
        <td>
            <input type="text" name="city" id="city" value="<?php echo esc_attr(get_the_author_meta('city', $user->ID)); ?>" class="regular-text" /><br />
            <span class="description" ><?php _e( "Please enter your city." ); ?></span>
        </td>
    </tr>
    <tr>
        <th><label for="postalcode" ><?php _e( "Postal Code" ); ?></label></th>
        <td>
            <input type="text" name="postalcode" id="postalcode" value="<?php echo esc_attr(get_the_author_meta('postalcode', $user->ID)); ?>" class="regular-text" /><br />
            <span class="description" ><?php _e( "Please enter your postal code." ); ?></span>
        </td>
    </tr>
</table>
<?php
/**
 * Message notice
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Supplier\Templates\Notices
 * @version 	1.0.0
 */

defined( "ABSPATH" ) || exit;

?>

<div class="message-actions" >
    <p class="dsm-bulkactions-sync dsm-my-5 dsm-fw-bold dsm-text-danger dsm-float-left dsm-d-none" >
        <?php _e( "This may take some time.", "woo_ds_supplier" ); ?>
    </p>
    <p class="dsm-bulkactions-import dsm-my-5 dsm-fw-bold dsm-text-danger dsm-float-left dsm-d-none" >
        <?php _e( "This may take some time. It is recommended to import one supplier at a time.", "woo_ds_supplier" ); ?>
    </p>
    <p class="dsm-bulkactions-del-products dsm-my-5 dsm-fw-bold dsm-text-danger dsm-float-left dsm-d-none" >
        <?php _e( "You will permanently delete the products.", "woo_ds_supplier" ); ?>
    </p>
    <p class="dsm-bulkactions-del-users dsm-my-5 dsm-fw-bold dsm-text-danger dsm-float-left dsm-d-none" >
        <?php _e( "You will permanently delete the suppliers and its products.", "woo_ds_supplier" ); ?>
    </p>
</div>

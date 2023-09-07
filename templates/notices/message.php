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
    <p class="dsm-bulkactions-del-orders dsm-my-5 dsm-fw-bold dsm-text-danger dsm-float-left dsm-d-none" >
        <?php _e( "You will permanently delete the orders.", "woo_ds_supplier" ); ?>
    </p>
    <p class="dsm-bulkactions-del-users dsm-my-5 dsm-fw-bold dsm-text-danger dsm-float-left dsm-d-none" >
        <?php _e( "You will permanently delete the shopkeepers and its orders.", "woo_ds_supplier" ); ?>
    </p>
    <p class="dsm-bulkactions-upd-shipped dsm-my-5 dsm-fw-bold dsm-text-danger dsm-float-left dsm-d-none" >
        <?php _e( "Only orders with 'processing' status will be updated.", "woo_ds_supplier" ); ?>
    </p>
</div>
